<?php

declare(strict_types=1);

namespace App\Application\Cifs\Query;

use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetIncidentsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetIncidentsQuery $query): array
    {
        $rows = $this->repository->findRegulationOrdersForCifsIncidentFormat();

        if (empty($rows)) {
            return [];
        }

        // Rows are sorted by measureId.
        // This means the data about a measure will come as a series of rows with the same measureId.
        // We build up the measure's <schedule> and push an incident whenever we encounter a new measureId.
        $incidentViews = [];
        $currentMeasure = $rows[0];
        $schedule = [];

        foreach ($rows as $row) {
            if ($row['measureId'] !== $currentMeasure['measureId']) {
                $address = LocationAddress::fromString($currentMeasure['address']);
                $incidentViews[] = self::makeIncidentView($currentMeasure, $address, $schedule);

                $currentMeasure = $row;
                $schedule = [];
            }

            $applicableDays = $row['applicableDays'];

            if (!empty($applicableDays)) {
                if (ApplicableDayEnum::hasAllValues($applicableDays)) {
                    $applicableDays = ['everyday'];
                }

                foreach ($applicableDays as $day) {
                    if (empty($schedule[$day])) {
                        $schedule[$day] = [];
                    }

                    $schedule[$day][] = [
                        'startTime' => $row['startTime'] ?? '00:00',
                        'endTime' => $row['endTime'] ?? '23:59',
                    ];
                }
            }
        }

        // Flush the last pending view.
        $address = LocationAddress::fromString($currentMeasure['address']);
        $incidentViews[] = self::makeIncidentView($currentMeasure, $address, $schedule);

        return $incidentViews;
    }

    private static function makeIncidentView(array $row, LocationAddress $address, array $schedule): CifsIncidentView
    {
        $subType = match ($row['category']) {
            RegulationOrderCategoryEnum::EVENT->value => 'ROAD_BLOCKED_EVENT',
            RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value => 'ROAD_BLOCKED_CONSTRUCTION',
            RegulationOrderCategoryEnum::INCIDENT->value => 'ROAD_BLOCKED_HAZARD',
            default => null,
        };

        // Adhere to XML schedule key order
        $dayOrder = ['everyday', ...ApplicableDayEnum::getValues()];
        uksort($schedule, fn ($day1, $day2) => array_search($day1, $dayOrder) - array_search($day2, $dayOrder));

        // Sort time spans by start time
        foreach (array_keys($schedule) as $day) {
            usort($schedule[$day], fn ($a, $b) => $a['startTime'] === $b['startTime'] ? 0 : ($a['startTime'] < $b['startTime'] ? -1 : 1));
        }

        return new CifsIncidentView(
            id: $row['measureId'],
            creationTime: $row['createdAt']->format('Y-m-d\TH:i:sP'),
            type: 'ROAD_CLOSED',
            subType: $subType,
            street: $address->getRoadName(),
            direction: 'BOTH_DIRECTIONS',
            polyline: sprintf('%f %f %f %f', $row['fromLatitude'], $row['fromLongitude'], $row['toLatitude'], $row['toLongitude']),
            startTime: ($row['periodStartDateTime'] ?? $row['regulationOrderStartDate'])->format('Y-m-d\TH:i:sP'),
            endTime: ($row['periodEndDateTime'] ?? $row['regulationOrderEndDate'])->format('Y-m-d\TH:i:sP'),
            // TODO: need a source organization ID provided by Waze.
            sourceReference: 'TODO',
            sourceName: 'DiaLog',
            schedule: $schedule,
        );
    }
}
