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

                    $direction = 'BOTH_DIRECTIONS';

                    if ($location->getNamedStreet()) {
                        $direction = DirectionEnum::toCifsDirection($location->getNamedStreet()->getDirection());
                    }

                    if ($location->getRawGeoJSON()) {
                        // Simplify the geometry to a (MULTI)LINESTRING with deduplicated segments.
                        $geometry = $this->polylineMaker->attemptMergeLines($geometry);
                    }

                    $polylines = $this->polylineMaker->getPolylines($geometry);

                    foreach ($incidentPeriods as $incidentPeriod) {
                        foreach ($polylines as $polyline) {
                            // The ID of a CIFS incident is opaque to Waze, we can define it as we want.
                            // But it must be "unique inside the feed and remain stable over an incident's lifetime".
                            // We include the regulation order identifier and location ID so that we can identify where an incident comes from.
                            $id = $identifier . ':' . $locationId . ':' . md5($polyline) . ':' . $incidentPeriod['id'];

                            $incidents[] = new CifsIncidentView(
                                id: $id,
                                creationTime: $incidentCreationTime,
                                type: 'ROAD_CLOSED',
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
        }

        return $incidents;
    }
}
