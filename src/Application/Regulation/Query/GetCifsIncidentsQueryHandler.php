<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetCifsIncidentsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetCifsIncidentsQuery $query): array
    {
        $rows = $this->repository->findRegulationOrdersForCifsIncidentFormat();

        if (empty($rows)) {
            return [];
        }

        // Rows are sorted by measureId and locationId.
        // This means that if there are 3 measures with 2 locations each, then we will get:
        // * 2 rows for the 1st location of the 1st measure
        // * then 2 rows for the 2nd location of the 1st measure
        // * then 2 rows for the 1st location of the 2nd measure
        // * Etc.
        // We build up the measure's <schedule> and push an incident whenever we encounter a new locationId.
        $incidentViews = [];
        $currentMeasure = $rows[0];
        $currentLocation = $rows[0];
        $readSchedule = true;
        $schedule = [];

        foreach ($rows as $row) {
            if ($row['locationId'] !== $currentLocation['locationId']) {
                $incidentViews[] = self::makeIncidentView($currentLocation, $schedule);

                $currentLocation = $row;
                // Once the rows of the first location have passed, the full measure schedule has been read.
                $readSchedule = false;
            }

            if ($row['measureId'] !== $currentMeasure['measureId']) {
                $currentMeasure = $row;
                $readSchedule = true;
                $schedule = [];
            }

            $applicableDays = $row['applicableDays'];

            if ($readSchedule && !empty($applicableDays)) {
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
        $incidentViews[] = self::makeIncidentView($currentLocation, $schedule);

        return $incidentViews;
    }

    private static function makeIncidentView(array $row, array $schedule): CifsIncidentView
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

        $geom = json_decode($row['geometry'], associative: true);

        $polyLineCoords = [];

        if ($geom['type'] === 'LineString') {
            foreach ($geom['coordinates'] as $coords) {
                $lon = $coords[0];
                $lat = $coords[1];
                $polyLineCoords[] = sprintf('%.6f %.6f', $lat, $lon);
            }
        } elseif ($geom['type'] === 'MultiLineString') {
            foreach ($geom['coordinates'] as $coordsList) {
                foreach ($coordsList as $coords) {
                    $lon = $coords[0];
                    $lat = $coords[1];
                    $polyLineCoords[] = sprintf('%.6f %.6f', $lat, $lon);
                }
            }
        }

        $polyLine = implode(' ', $polyLineCoords);

        return new CifsIncidentView(
            id: $row['locationId'],
            creationTime: $row['createdAt']->format('Y-m-d\TH:i:sP'),
            type: 'ROAD_CLOSED',
            subType: $subType,
            street: $row['roadName'] ?? $row['roadNumber'],
            direction: 'BOTH_DIRECTIONS',
            polyline: $polyLine,
            startTime: ($row['periodStartDateTime'] ?? $row['regulationOrderStartDate'])->format('Y-m-d\TH:i:sP'),
            endTime: ($row['periodEndDateTime'] ?? $row['regulationOrderEndDate'])->format('Y-m-d\TH:i:sP'),
            schedule: $schedule,
        );
    }
}
