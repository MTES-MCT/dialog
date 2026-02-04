<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Cifs\PolylineMakerInterface;
use App\Application\DateUtilsInterface;
use App\Application\Regulation\DTO\CifsFilterSet;
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

final class GetCifsIncidentsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
        private PolylineMakerInterface $polylineMaker,
        private DateUtilsInterface $dateUtils,
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

            $subType = match ($regulationOrder->getSubject()) {
                RegulationSubjectEnum::EVENT->value => 'ROAD_BLOCKED_EVENT',
                RegulationSubjectEnum::ROAD_MAINTENANCE->value => 'ROAD_BLOCKED_CONSTRUCTION',
                RegulationSubjectEnum::INCIDENT->value => 'ROAD_BLOCKED_HAZARD',
                default => null,
            };

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

                        $incidentPeriods[] = [
                            'id' => $period->getUuid(),
                            'start' => $period->getStartDateTime(),
                            'end' => $period->getEndDateTime(),
                            'schedule' => $schedule,
                        ];
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
                    $street = $location->getCifsStreetLabel();

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

                    if ($location->getNamedStreet()) {
                        $direction = DirectionEnum::toCifsDirection($location->getNamedStreet()->getDirection());
                    }

                    if ($location->getRawGeoJSON()) {
                        // Simplify the geometry to a (MULTI)LINESTRING with deduplicated segments.
                        $geometry = $this->polylineMaker->attemptMergeLines($geometry);
                    }

                    $polyline = $this->polylineMaker->getMergedPolyline($geometry);
                    if (!$polyline) {
                        continue;
                    }

                    foreach ($incidentPeriods as $incidentPeriod) {
                        // L'ID d'un incident CIFS est opaque pour Waze, on le définit comme on veut.
                        // Il doit être "unique dans le flux et rester stable pendant toute la vie de l'incident".
                        // Un incident logique par (règlement, lieu, période) — pas de hash de la polyline pour éviter les doublons avec un MultiLineString.
                        $id = $identifier . ':' . $locationId . ':' . $incidentPeriod['id'];

                        $incidents[] = new CifsIncidentView(
                            id: $id,
                            creationTime: $incidentCreationTime,
                            type: MeasureTypeEnum::from($measure->getType())->getCifsKey(),
                            subType: $subType,
                            street: $street,
                            direction: $direction,
                            polyline: $polyline,
                            startTime: $incidentPeriod['start'],
                            endTime: $incidentPeriod['end'],
                            schedule: $incidentPeriod['schedule'],
                        );
                    }
                }
            }
        }

        return $incidents;
    }
}
