<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\Exception\ZoneWithoutStreetsException;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\Location\ExceptionsSignature;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\WholeCityException;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Specification\CanOrganizationInterveneOnGeometry;

final class SaveLocationCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private LocationRepositoryInterface $locationRepository,
        private IdFactoryInterface $idFactory,
        private CanOrganizationInterveneOnGeometry $canOrganizationInterveneOnGeometry,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();
        $roadCommand = $command->getRoadCommand();
        $roadCommand->clean();
        $organizationUuid = $command->organization->getUuid();

        // Update location

        if ($location = $command->location) {
            $roadCommand->setLocation($location);
            $geometry = $this->queryBus->handle($roadCommand->getGeometryQuery());
            $this->checkGeometry($roadCommand, $organizationUuid, $geometry);
            $exceptions = $this->prepareExceptions($roadCommand, $location);

            $location->update($command->roadType, $geometry);
            $this->applyRoadCommand($roadCommand, $location, $exceptions);

            if ($deleteCommand = $command->getRoadDeleteCommand()) {
                $this->commandBus->handle($deleteCommand);
            }

            return $location;
        }

        // Create location
        // Tout ce qui peut échouer (géocodage, compétence, exceptions) est calculé avant de persister quoi
        // que ce soit : l'API peut ainsi rejeter cette localisation seule et poursuivre avec les suivantes.

        $geometry = $this->queryBus->handle($roadCommand->getGeometryQuery());
        $this->checkGeometry($roadCommand, $organizationUuid, $geometry);
        $exceptions = $this->prepareExceptions($roadCommand, null);

        $location = $this->locationRepository->add(
            new Location(
                uuid: $this->idFactory->make(),
                measure: $command->measure,
                roadType: $command->roadType,
                geometry: $geometry,
            ),
        );
        $roadCommand->setLocation($location);
        $this->applyRoadCommand($roadCommand, $location, $exceptions);
        $command->measure->addLocation($location);

        return $location;
    }

    private function checkGeometry(RoadCommandInterface $roadCommand, string $organizationUuid, string $geometry): void
    {
        // Pour une zone, la géométrie enregistrée (les tronçons de rues couverts) peut être
        // vide si le périmètre ne contient aucune rue, et ST_Intersects avec une géométrie
        // vide renvoie toujours faux. La compétence de l'organisation s'évalue donc sur le
        // polygone dessiné, qui représente la zone d'intervention voulue.
        $competenceGeometry = $roadCommand instanceof SaveZoneCommand && $roadCommand->geometry
            ? $roadCommand->geometry
            : $geometry;

        if (!$this->canOrganizationInterveneOnGeometry->isSatisfiedBy($organizationUuid, $competenceGeometry)) {
            throw new OrganizationCannotInterveneOnGeometryException();
        }

        // Une zone sans rue n'aurait aucun linéaire exploitable (carte, exports DATEX II et
        // CIFS) : on refuse l'enregistrement pour que l'usager corrige son tracé.
        if ($roadCommand instanceof SaveZoneCommand && GeoJSON::isEmptyGeometryCollection($geometry)) {
            throw new ZoneWithoutStreetsException();
        }
    }

    /**
     * @param array<array{command: SaveWholeCityExceptionCommand, geometry: ?string}> $exceptions
     */
    private function applyRoadCommand(RoadCommandInterface $roadCommand, Location $location, array $exceptions): void
    {
        // « Ville entière » n'a pas de sous-entité dédiée : ses données (ville + voies exclues)
        // vivent sur la localisation elle-même, donc rien à persister via un sous-handler.
        if ($roadCommand instanceof SaveWholeCityCommand) {
            $location->setWholeCity($roadCommand->cityCode, $roadCommand->cityLabel);
        } else {
            $this->commandBus->handle($roadCommand);
        }

        $this->replaceExceptions($exceptions, $location);
    }

    /**
     * Calcule les géométries des exceptions sans rien persister.
     *
     * @return array<array{command: SaveWholeCityExceptionCommand, geometry: ?string}>
     */
    private function prepareExceptions(RoadCommandInterface $roadCommand, ?Location $location): array
    {
        // « Ville entière », « Tracé de zone » et « Tracé libre » supportent des exceptions ;
        // pour les autres types, on synchronise avec une liste vide afin de purger les
        // exceptions restantes après un changement de type de localisation.
        $exceptionCommands = match (true) {
            $roadCommand instanceof SaveWholeCityCommand,
            $roadCommand instanceof SaveZoneCommand,
            $roadCommand instanceof SaveRawGeoJSONCommand => $roadCommand->exceptions,
            default => [],
        };

        // On garde les géométries des exceptions existantes : une exception inchangée (même signature)
        // ne doit pas être re-géocodée à chaque enregistrement de la mesure.
        $existingGeometries = [];
        foreach ($location?->getExceptions() ?? [] as $existingException) {
            $existingGeometries[ExceptionsSignature::ofException($existingException)] ??= $existingException->getGeometry();
        }

        $prepared = [];

        foreach ($exceptionCommands as $exceptionCommand) {
            $signature = ExceptionsSignature::ofCommand($exceptionCommand);
            $isReused = \array_key_exists($signature, $existingGeometries);

            if ($exceptionCommand->computedGeometry !== null) {
                // Déjà calculée par la requête de géométrie parente (soustraction).
                $geometry = $exceptionCommand->computedGeometry;
            } elseif ($isReused) {
                $geometry = $existingGeometries[$signature];
            } else {
                $geometryQuery = $exceptionCommand->getGeometryQuery();
                $geometry = $geometryQuery ? $this->queryBus->handle($geometryQuery) : null;
            }

            // Comme pour une localisation « Tracé de zone » : une zone sans rue ne soustrairait
            // rien, on refuse l'enregistrement pour que l'usager corrige son tracé. Les
            // exceptions réutilisées telles quelles ne sont pas re-contrôlées.
            if (!$isReused
                && $exceptionCommand->roadType === RoadTypeEnum::ZONE->value
                && ($geometry === null || GeoJSON::isEmptyGeometryCollection($geometry))) {
                throw new ZoneWithoutStreetsException();
            }

            $prepared[] = ['command' => $exceptionCommand, 'geometry' => $geometry];
        }

        return $prepared;
    }

    /**
     * @param array<array{command: SaveWholeCityExceptionCommand, geometry: ?string}> $exceptions
     */
    private function replaceExceptions(array $exceptions, Location $location): void
    {
        // On remplace l'ensemble des exceptions (l'orphan removal supprime les anciennes).
        foreach ($location->getExceptions() as $existingException) {
            $location->removeException($existingException);
        }

        foreach ($exceptions as ['command' => $exceptionCommand, 'geometry' => $geometry]) {
            $location->addException(
                new WholeCityException(
                    uuid: $this->idFactory->make(),
                    location: $location,
                    roadType: $exceptionCommand->roadType,
                    label: $exceptionCommand->getLabel(),
                    geometry: $geometry,
                    data: $exceptionCommand->toData(),
                ),
            );
        }
    }
}
