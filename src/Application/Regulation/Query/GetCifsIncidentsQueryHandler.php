<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

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

            $incidentCreationTime = $regulationOrderRecord->getCreatedAt()->format('Y-m-d\TH:i:sP');
            $incidentStartTime = $regulationOrder->getStartDate()->format('Y-m-d\TH:i:sP');
            $incidentEndTime = $regulationOrder->getEndDate()->format('Y-m-d\TH:i:sP');

            /** @var Measure $measure */
            foreach ($regulationOrder->getMeasures() as $measure) {
                $schedule = [];

                /** @var Period $period */
                foreach ($measure->getPeriods() as $period) {
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

                    foreach ($applicableDays as $day) {
                        if (!isset($schedule[$day])) {
                            $schedule[$day] = [];
                        }

                        foreach ($timeSpans as $timeSpan) {
                            $schedule[$day][] = $timeSpan;
                        }
                    }
                }

                // Adhere to key order in CIFS <schedule> XML element
                $dayOrder = ['everyday', ...ApplicableDayEnum::getValues()];
                uksort($schedule, fn ($day1, $day2) => array_search($day1, $dayOrder) - array_search($day2, $dayOrder));

                // Sort time spans by start time as per CIFS examples
                foreach ($schedule as $day => $timeSpans) {
                    usort($timeSpans, fn ($a, $b) => $a['startTime'] === $b['startTime'] ? 0 : ($a['startTime'] < $b['startTime'] ? -1 : 1));
                    $schedule[$day] = $timeSpans;
                }

                /** @var Location $location */
                foreach ($measure->getLocations() as $location) {
                    $street = $location->getRoadName() ?? $location->getRoadNumber();
                    $polylines = $this->repository->convertToCifsPolylines($location->getGeometry());

                    foreach ($polylines as $index => $polyline) {
                        $incidents[] = new CifsIncidentView(
                            id: $location->getUuid() . '#' . $index,
                            creationTime: $incidentCreationTime,
                            type: 'ROAD_CLOSED',
                            subType: $subType,
                            street: $street,
                            direction: 'BOTH_DIRECTIONS',
                            polyline: $polyline,
                            startTime: $incidentStartTime,
                            endTime: $incidentEndTime,
                            schedule: $schedule,
                        );
                    }
                }
            }
        }

        return $incidents;
    }
}
