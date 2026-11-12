<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Cifs\PolylineMakerInterface;
use App\Application\DateUtilsInterface;
use App\Application\Regulation\DTO\CifsFilterSet;
use App\Application\Regulation\NumberedRoadLabelMaker;
use App\Application\Regulation\PeriodPublicHolidaysProvider;
use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetCifsIncidentsQueryHandler
{
    private const DEFAULT_SOURCE_REFERENCE = 'dialog.beta.gouv.fr';

    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
        private PolylineMakerInterface $polylineMaker,
        private DateUtilsInterface $dateUtils,
        private TranslatorInterface $translator,
        private NumberedRoadLabelMaker $numberedRoadLabelMaker,
        private PeriodPublicHolidaysProvider $publicHolidaysProvider,
        private CifsFilterSet $cifsFilterSet = new CifsFilterSet(),
    ) {
    }

    public function __invoke(GetCifsIncidentsQuery $query): array
    {
        $regulationOrderRecords = $this->repository->findRegulationOrdersForCifsIncidentFormat(
            $this->cifsFilterSet->allowedSources,
            $this->cifsFilterSet->excludedIdentifiers,
            $this->cifsFilterSet->allowedLocationIds,
            $this->cifsFilterSet->excludedOrgUuids,
        );
        $uuids = [];

        /** @var RegulationOrderRecord $regulationOrderRecord */
        foreach ($regulationOrderRecords as $regulationOrderRecord) {
            $uuids[] = $regulationOrderRecord->getUuid();
        }

        $overallDates = $this->repository->getOverallDatesByRegulationUuids($uuids);
        // Reference: https://developers.google.com/waze/data-feed/cifs-specification?hl=fr
        $incidents = [];

        /** @var RegulationOrderRecord $regulationOrderRecord */
        foreach ($regulationOrderRecords as $regulationOrderRecord) {
            $uuid = $regulationOrderRecord->getUuid();
            $regulationOrder = $regulationOrderRecord->getRegulationOrder();
            $identifier = $regulationOrder->getIdentifier();
            $regulationSubject = $regulationOrder->getSubject();

            // La source de l'incident est l'organisation émettrice de l'arrêté, identifiée par son code
            // (code INSEE pour une commune). À défaut de code, on retombe sur la référence de DiaLog.
            $organization = $regulationOrderRecord->getOrganization();
            $sourceReference = $organization->getCode() ?: self::DEFAULT_SOURCE_REFERENCE;
            $sourceName = \sprintf('%s (DiaLog)', $organization->getName());

            $incidentCreationTime = $regulationOrderRecord->getCreatedAt();
            $regulationStart = $overallDates[$uuid]['overallStartDate'];
            $regulationEnd = $overallDates[$uuid]['overallEndDate'];

            /** @var Measure $measure */
            foreach ($regulationOrder->getMeasures() as $measure) {
                /** @var Period[] $periods */
                $periods = $measure->getPeriods();

                $incidentPeriods = [];

                if (\count($periods) > 0) {
                    foreach ($periods as $period) {
                        $applicableDays = $period->getDailyRange()?->getApplicableDays() ?? [];

                        if (ApplicableDayEnum::hasAllValues($applicableDays)) {
                            $applicableDays = ['everyday'];
                        }

                        /** @var TimeSlot[] $timeSlots */
                        $timeSlots = $period->getTimeSlots();

                        $isEveryDay = false;
                        $isAllTheTime = false;

                        if (!$applicableDays && $timeSlots) {
                            $isEveryDay = true;
                            $applicableDays = ['everyday'];
                        }

                        if (\count($timeSlots) > 0) {
                            $timeSpans = [];

                            foreach ($timeSlots as $timeSlot) {
                                $timeSpans[] = [
                                    'startTime' => $timeSlot->getStartTime(),
                                    'endTime' => $timeSlot->getEndTime(),
                                ];
                            }
                        } else {
                            $isAllTheTime = true;
                            $timeSpans = [['startTime' => new \DateTimeImmutable('00:00'), 'endTime' => new \DateTimeImmutable('23:59')]];
                        }

                        $schedule = [];

                        if (!$isEveryDay || !$isAllTheTime) {
                            foreach ($applicableDays as $day) {
                                $schedule[$day] = $timeSpans;
                            }
                        }

                        // Adhere to key order in CIFS <schedule> XML element
                        $dayOrder = ['everyday', ...ApplicableDayEnum::getValues()];
                        uksort($schedule, fn ($day1, $day2) => array_search($day1, $dayOrder) - array_search($day2, $dayOrder));

                        foreach ($this->splitAroundPublicHolidays($period) as $segment) {
                            $incidentPeriods[] = [...$segment, 'schedule' => $schedule];
                        }
                    }
                } else {
                    $incidentPeriods[] = [
                        'id' => '0',
                        'start' => $regulationStart,
                        // Use next day to include the end date
                        'end' => $this->dateUtils->addDays($regulationEnd, 1),
                        'schedule' => [],
                    ];
                }

                /** @var Location $location */
                foreach ($measure->getLocations() as $location) {
                    $locationId = $location->getUuid();
                    $numberedRoad = $location->getNumberedRoad();
                    $street = $numberedRoad
                        ? $this->numberedRoadLabelMaker->make($numberedRoad)
                        : $location->getCifsStreetLabel();

                    $geometry = $location->getGeometry();
                    if ($geometry === null) {
                        continue;
                    }

                    // S'assurer que la géométrie est du GeoJSON LineString ou MultiLineString (sans GeometryCollection, points, etc.).
                    $geometry = $this->polylineMaker->normalizeToLineStringGeoJSON($geometry);
                    if ($geometry === null) {
                        continue;
                    }
                    $direction = 'BOTH_DIRECTIONS';
                    $roadDirection = $location->getNamedStreet()?->getDirection() ?? $numberedRoad?->getDirection();

                    if ($roadDirection) {
                        $direction = DirectionEnum::toCifsDirection($roadDirection);
                    }

                    if ($location->getRawGeoJSON()) {
                        // Simplify the geometry to a (MULTI)LINESTRING with deduplicated segments.
                        $geometry = $this->polylineMaker->attemptMergeLines($geometry);
                    }

                    $polyline = $this->polylineMaker->getMergedPolyline($geometry);
                    if (!$polyline) {
                        continue;
                    }

                    // La géométrie est stockée de A vers B. En CIFS, le sens d'un incident ONE_DIRECTION est donné
                    // par l'ordre des points de la polyligne : pour une restriction de B vers A, on la retourne.
                    if ($roadDirection === DirectionEnum::B_TO_A->value) {
                        $polyline = self::reversePolyline($polyline);
                    }

                    foreach ($incidentPeriods as $incidentPeriod) {
                        // L'ID d'un incident CIFS est opaque pour Waze, on le définit comme on veut.
                        // Il doit être "unique dans le flux et rester stable pendant toute la vie de l'incident".
                        // Un incident logique par (règlement, lieu, période) — pas de hash de la polyline pour éviter les doublons avec un MultiLineString.
                        $id = $identifier . ':' . $locationId . ':' . $incidentPeriod['id'];
                        $measureType = MeasureTypeEnum::from($measure->getType());

                        $incidents[] = new CifsIncidentView(
                            id: $id,
                            creationTime: $incidentCreationTime,
                            type: $measureType->getCifsKey(),
                            subType: $this->getSubType($regulationSubject, $measureType),
                            description: $this->translator->trans('regulation.measure.type.' . $measureType->value),
                            street: $street,
                            direction: $direction,
                            polyline: $polyline,
                            startTime: $incidentPeriod['start'],
                            endTime: $incidentPeriod['end'],
                            sourceReference: $sourceReference,
                            sourceName: $sourceName,
                            regulationOrderRecordUuid: $uuid,
                            schedule: $incidentPeriod['schedule'],
                        );
                    }
                }
            }
        }

        return $incidents;
    }

    /**
     * Inverse l'ordre des points d'une polyligne CIFS ("lat lon lat lon ...").
     */
    private static function reversePolyline(string $polyline): string
    {
        $points = array_chunk(explode(' ', $polyline), 2);

        return implode(' ', array_merge(...array_reverse($points)));
    }

    /**
     * CIFS ne permet pas d'exprimer d'exceptions : la période est découpée
     * en plusieurs incidents qui excluent les jours fériés à venir.
     */
    private function splitAroundPublicHolidays(Period $period): array
    {
        $start = $period->getStartDateTime();
        $end = $period->getEndDateTime();
        $now = $this->dateUtils->getNow();
        $segments = [];
        $cursor = $start;

        // Le découpage et les identifiants doivent être indépendants de la date de consultation
        // pour respecter le contrat de stabilité des identifiants CIFS : on part de la liste
        // complète et déterministe des jours fériés exclus (sans borner le début à aujourd'hui).
        foreach ($this->publicHolidaysProvider->getExcludedPublicHolidays($period, limitToUpcoming: false) as $holidayStart) {
            $holidayEnd = $holidayStart->modify('+1 day');

            if ($holidayStart > $cursor) {
                $segments[] = ['start' => $cursor, 'end' => $holidayStart];
            }

            $cursor = max($cursor, $holidayEnd);
        }

        if ($end === null || $cursor < $end) {
            $segments[] = ['start' => $cursor, 'end' => $end];
        }

        // Le premier segment conserve l'identifiant de la période pour rester stable
        foreach ($segments as $index => $segment) {
            $segments[$index]['id'] = $index === 0
                ? $period->getUuid()
                : \sprintf('%s:%s', $period->getUuid(), $segment['start']->format('Ymd'));
        }

        // On ne publie pas les segments déjà expirés (jour férié écoulé ou période terminée),
        // sans jamais recalculer le découpage ni les identifiants des segments encore actifs.
        $segments = array_filter(
            $segments,
            static fn (array $segment): bool => $segment['end'] === null || $segment['end'] > $now,
        );

        return array_values($segments);
    }

    private function getSubType(?string $regulationSubject, MeasureTypeEnum $measureType): ?string
    {
        if ($measureType === MeasureTypeEnum::ALTERNATE_ROAD) {
            return 'HAZARD_ON_ROAD_LANE_CLOSED';
        }

        return match ($regulationSubject) {
            RegulationSubjectEnum::EVENT->value => 'ROAD_BLOCKED_EVENT',
            RegulationSubjectEnum::ROAD_MAINTENANCE->value => 'ROAD_BLOCKED_CONSTRUCTION',
            RegulationSubjectEnum::INCIDENT->value => 'ROAD_BLOCKED_HAZARD',
            default => null,
        };
    }
}
