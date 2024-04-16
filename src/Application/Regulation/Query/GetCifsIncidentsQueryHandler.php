<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Cifs\PolylineMakerInterface;
use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetCifsIncidentsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
        private PolylineMakerInterface $polylineMaker,
    ) {
    }

    public function __invoke(GetCifsIncidentsQuery $query): array
    {
        $regulationOrderRecords = $this->repository->findRegulationOrdersForCifsIncidentFormat();

        // Reference: https://developers.google.com/waze/data-feed/cifs-specification?hl=fr
        $incidents = [];

        /** @var RegulationOrderRecord $regulationOrderRecord */
        foreach ($regulationOrderRecords as $regulationOrderRecord) {
            $regulationOrder = $regulationOrderRecord->getRegulationOrder();

            $subType = match ($regulationOrder->getCategory()) {
                RegulationOrderCategoryEnum::EVENT->value => 'ROAD_BLOCKED_EVENT',
                RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value => 'ROAD_BLOCKED_CONSTRUCTION',
                RegulationOrderCategoryEnum::INCIDENT->value => 'ROAD_BLOCKED_HAZARD',
                default => null,
            };

            $incidentCreationTime = $regulationOrderRecord->getCreatedAt();
            $regulationStart = $regulationOrder->getStartDate();
            $regulationEnd = $regulationOrder->getEndDate();

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

                        if ($timeSlots) {
                            $timeSpans = [];

                            foreach ($timeSlots as $timeSlot) {
                                $timeSpans[] = [
                                    'startTime' => $timeSlot->getStartTime(),
                                    'endTime' => $timeSlot->getEndTime(),
                                ];
                            }
                        } else {
                            $timeSpans = [['startTime' => new \DateTimeImmutable('00:00'), 'endTime' => new \DateTimeImmutable('23:59')]];
                        }

                        $schedule = [];

                        foreach ($applicableDays as $day) {
                            $schedule[$day] = $timeSpans;
                        }

                        // Adhere to key order in CIFS <schedule> XML element
                        $dayOrder = ['everyday', ...ApplicableDayEnum::getValues()];
                        uksort($schedule, fn ($day1, $day2) => array_search($day1, $dayOrder) - array_search($day2, $dayOrder));

                        // Sort time spans by start time as per CIFS examples
                        foreach ($schedule as $day => $timeSpans) {
                            usort($timeSpans, fn ($a, $b) => $a['startTime'] === $b['startTime'] ? 0 : ($a['startTime'] < $b['startTime'] ? -1 : 1));
                            $schedule[$day] = $timeSpans;
                        }

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
                        'end' => $regulationEnd,
                        'schedule' => [],
                    ];
                }

                /** @var Location $location */
                foreach ($measure->getLocations() as $location) {
                    $locationId = $location->getUuid();
                    $street = $location->getRoadName() ?? $location->getRoadNumber();
                    $polylines = $this->polylineMaker->getPolylines($location->getGeometry());

                    foreach ($incidentPeriods as $incidentPeriod) {
                        foreach ($polylines as $polyline) {
                            // The ID of a CIFS incident is opaque to Waze, we can define it as we want.
                            // But it must be "unique inside the feed and remain stable over an incident's lifetime".
                            // For the ID to be unique, it should contain the location ID, a hash of the polyline, and the period ID.
                            $id = $locationId . ':' . md5($polyline) . ':' . $incidentPeriod['id'];

                            $incidents[] = new CifsIncidentView(
                                id: $id,
                                creationTime: $incidentCreationTime,
                                type: 'ROAD_CLOSED',
                                subType: $subType,
                                street: $street,
                                direction: 'BOTH_DIRECTIONS',
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
