<?php

declare(strict_types=1);

namespace App\Application\Regulation;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\AllLocationsRejectedException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\IntersectionGeocodingFailureException;
use App\Application\Exception\LaneGeocodingFailureException;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\Exception\ZoneWithoutStreetsException;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\View\RejectedLocationView;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\RejectedLocation;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use App\Domain\Regulation\Repository\RejectedLocationRepositoryInterface;
use App\Domain\User\Organization;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Enregistre les mesures d'un arrêté reçu via l'API d'écriture en acceptant partiellement ses localisations
 * (emprises) : une localisation invalide (valeur manquante, géocodage en échec, hors compétence...) est rejetée
 * sans empêcher l'intégration des autres. Une erreur portant sur la mesure elle-même (type, périodes,
 * véhicules...) rejette toutes ses localisations. Les localisations rejetées sont stockées.
 */
final class ApiMeasuresSaver
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ObjectMapperInterface $objectMapper,
        private MeasureRepositoryInterface $measureRepository,
        private RejectedLocationRepositoryInterface $rejectedLocationRepository,
        private IdFactoryInterface $idFactory,
        private DateUtilsInterface $dateUtils,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array $measureDtos DTOs des mesures (mappés vers SaveMeasureCommand)
     * @param array $rawMeasures mesures telles qu'envoyées dans la requête, pour conserver les emprises rejetées
     *
     * @throws AllLocationsRejectedException si des localisations ont été envoyées mais qu'aucune n'a été intégrée
     */
    public function save(
        RegulationOrderRecord $regulationOrderRecord,
        Organization $organization,
        array $measureDtos,
        array $rawMeasures,
    ): void {
        $regulationOrder = $regulationOrderRecord->getRegulationOrder();
        /** @var RejectedLocationView[] $rejectedLocations */
        $rejectedLocations = [];
        $acceptedLocationsCount = 0;

        foreach (array_values($measureDtos) as $measureIndex => $measureDto) {
            $rawMeasure = \is_array($rawMeasures[$measureIndex] ?? null) ? $rawMeasures[$measureIndex] : [];
            $rawLocations = \is_array($rawMeasure['locations'] ?? null) ? array_values($rawMeasure['locations']) : [];
            unset($rawMeasure['locations']);

            $measureCommand = SaveMeasureCommand::create($regulationOrder);
            $this->objectMapper->map($measureDto, $measureCommand);
            $measureCommand->locations = array_values($measureCommand->locations);
            $measureCommand->rejectFailingLocations = true;

            foreach ($measureCommand->locations as $locationCommand) {
                $locationCommand->organization = $organization;
            }

            $submittedIndexes = array_keys($measureCommand->locations);
            // Position courante dans $measureCommand->locations => index d'origine dans la requête
            $originalIndexes = $submittedIndexes;
            /** @var array<int, array> $locationErrors index d'origine => erreurs */
            $locationErrors = [];
            $measure = null;

            // La validation a lieu avant toute écriture : on retire les localisations invalides puis on réessaie.
            while (true) {
                try {
                    $measure = $this->commandBus->handle($measureCommand);
                    break;
                } catch (ValidationFailedException $e) {
                    $measureErrors = [];
                    $failingPositions = [];

                    foreach ($e->getViolations() as $violation) {
                        $path = $violation->getPropertyPath();

                        if (preg_match('/^locations\[(\d+)\](.*)$/', $path, $matches) && isset($originalIndexes[(int) $matches[1]])) {
                            $position = (int) $matches[1];
                            $originalIndex = $originalIndexes[$position];
                            $failingPositions[$position] = true;
                            $locationErrors[$originalIndex][] = $this->error(
                                \sprintf('measures[%d].locations[%d]%s', $measureIndex, $originalIndex, $matches[2]),
                                (string) $violation->getMessage(),
                            );
                        } else {
                            $measureErrors[] = $this->error(
                                \sprintf('measures[%d]%s', $measureIndex, $path === '' ? '' : '.' . $path),
                                (string) $violation->getMessage(),
                            );
                        }
                    }

                    if ($measureErrors) {
                        // Sans localisation à rejeter, l'erreur de mesure fait échouer l'arrêté (comportement historique).
                        if (!$submittedIndexes) {
                            throw $e;
                        }

                        foreach ($submittedIndexes as $originalIndex) {
                            $locationErrors[$originalIndex] = array_merge($measureErrors, $locationErrors[$originalIndex] ?? []);
                        }

                        break;
                    }

                    foreach (array_keys($failingPositions) as $position) {
                        unset($measureCommand->locations[$position], $originalIndexes[$position]);
                    }

                    $measureCommand->locations = array_values($measureCommand->locations);
                    $originalIndexes = array_values($originalIndexes);

                    if (!$measureCommand->locations) {
                        break;
                    }
                }
            }

            if ($measure !== null) {
                foreach ($measureCommand->rejectedLocations as $position => $exception) {
                    $originalIndex = $originalIndexes[$position];
                    $locationErrors[$originalIndex] = array_merge(
                        $locationErrors[$originalIndex] ?? [],
                        $this->errorsFromException($exception, \sprintf('measures[%d].locations[%d]', $measureIndex, $originalIndex), $organization),
                    );
                }

                $acceptedCount = \count($measureCommand->locations) - \count($measureCommand->rejectedLocations);

                if ($acceptedCount === 0 && $submittedIndexes) {
                    // Toutes les localisations de la mesure ont été rejetées : la mesure n'a plus de sens.
                    $this->measureRepository->delete($measure);
                } else {
                    $regulationOrder->addMeasure($measure);
                    $acceptedLocationsCount += $acceptedCount;
                }
            }

            ksort($locationErrors);

            foreach ($locationErrors as $originalIndex => $errors) {
                $rejectedLocations[] = new RejectedLocationView(
                    measureIndex: $measureIndex,
                    locationIndex: $originalIndex,
                    location: \is_array($rawLocations[$originalIndex] ?? null) ? $rawLocations[$originalIndex] : [],
                    measure: $rawMeasure,
                    errors: $errors,
                );
            }
        }

        if ($rejectedLocations && $acceptedLocationsCount === 0) {
            throw new AllLocationsRejectedException($rejectedLocations);
        }

        $this->rejectedLocationRepository->deleteByRegulationOrderRecordUuid($regulationOrderRecord->getUuid());
        $now = $this->dateUtils->getNow();

        foreach ($rejectedLocations as $view) {
            $this->rejectedLocationRepository->add(
                new RejectedLocation(
                    uuid: $this->idFactory->make(),
                    regulationOrderRecord: $regulationOrderRecord,
                    measureIndex: $view->measureIndex,
                    locationIndex: $view->locationIndex,
                    location: $view->location,
                    measure: $view->measure,
                    errors: $view->errors,
                    createdAt: $now,
                ),
            );
        }
    }

    private function error(string $propertyPath, string $title): array
    {
        return ['propertyPath' => $propertyPath, 'title' => $title];
    }

    private function errorsFromException(\Throwable $exception, string $pathPrefix, Organization $organization): array
    {
        if ($exception instanceof ValidationFailedException) {
            $errors = [];

            foreach ($exception->getViolations() as $violation) {
                $path = $violation->getPropertyPath();
                $errors[] = $this->error($pathPrefix . ($path === '' ? '' : '.' . $path), (string) $violation->getMessage());
            }

            return $errors;
        }

        $title = match (true) {
            $exception instanceof IntersectionGeocodingFailureException => $this->translator->trans('regulation.location.error.intersection_not_found', [], 'validators'),
            $exception instanceof LaneGeocodingFailureException => $this->translator->trans('regulation.location.error.lane_geocoding_failed', [], 'validators'),
            $exception instanceof AbscissaOutOfRangeException => $this->translator->trans('regulation.location.error.abscissa_out_of_range', [], 'validators'),
            $exception instanceof RoadGeocodingFailureException => $this->translator->trans('regulation.location.error.numbered_road_geocoding_failed', [], 'validators'),
            $exception instanceof GeocodingFailureException => $this->translator->trans('regulation.location.error.geocoding_failed', [], 'validators'),
            $exception instanceof OrganizationCannotInterveneOnGeometryException => $this->translator->trans('regulation.location.error.organization_cannot_intervene_on_geometry', ['%organizationName%' => $organization->getName()], 'validators'),
            $exception instanceof ZoneWithoutStreetsException => $this->translator->trans('regulation.location.error.zone_without_streets', [], 'validators'),
            default => $exception->getMessage(),
        };

        return [$this->error($pathPrefix, $title)];
    }
}
