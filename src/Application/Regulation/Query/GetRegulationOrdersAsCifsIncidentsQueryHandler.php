<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrdersAsCifsIncidentsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    private static function makeIncidentView(array $row, LocationAddress $address, array $schedule): CifsIncidentView
    {
        $subType = match ($row['category']) {
            RegulationOrderCategoryEnum::EVENT->value => 'ROAD_BLOCKED_EVENT',
            RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value => 'ROAD_BLOCKED_CONSTRUCTION',
            RegulationOrderCategoryEnum::INCIDENT->value => 'ROAD_BLOCKED_HAZARD',
            default => null,
        };

        return new CifsIncidentView(
            id: $row['measureId'],
            creationTime: $row['createdAt']->format('Y-m-d\TH:i:s'),
            type: 'ROAD_CLOSED',
            subType: $subType,
            // TODO: this should come from Waze's reverse geocoding API. The incident might not show up if our road name
            // is not the same as Waze's.
            // https://support.google.com/waze/partners/answer/11486981?hl=fr
            street: $address->getRoadName(),
            direction: 'BOTH_DIRECTIONS',
            polyline: sprintf('%f %f %f %f', $row['fromLatitude'], $row['fromLongitude'], $row['toLatitude'], $row['toLongitude']),
            startTime: $row['startDate']->format('Y-m-d\TH:i:s'),
            endTime: $row['endDate']->format('Y-m-d\TH:i:s'),
            // TODO: use a source organization ID provided by Waze.
            sourceReference: 'TODO',
            sourceName: 'DiaLog',
            schedule: $schedule,
        );
    }

    /**
     * @return CifsIncidentView[]
     */
    public function __invoke(GetRegulationOrdersAsCifsIncidentsQuery $query): array
    {
        $rows = $this->repository->findRegulationOrdersForCifsIncidentFormat();

        if (empty($rows)) {
            return [];
        }

        // There are N rows per measure, where N is the number of periods for the measure.
        // If there are no periods, then there is only 1 row for the measure with null period field values.
        // Rows are sorted by measure UUID.
        // So we iterate over rows and push a new view when the measure UUID changes.
        $incidentViews = [];
        $currentMeasure = $rows[0];
        $schedule = [];

        foreach ($rows as $row) {
            if ($row['measureId'] !== $currentMeasure['measureId']) {
                // Check for CIFS required data

                if ($currentMeasure['fromLongitude'] === null || $currentMeasure['toLongitude'] === null) {
                    $currentMeasure = $row;
                    $schedule = [];
                    continue;
                }

                $address = LocationAddress::fromString($currentMeasure['address']);

                if ($address->getRoadName() === null) {
                    $currentMeasure = $row;
                    $schedule = [];
                    continue;
                }

                $incidentViews[] = self::makeIncidentView($currentMeasure, $address, $schedule);

                $currentMeasure = $row;
                $schedule = [];
            }

            $applicableDays = $row['applicableDays'] === ApplicableDayEnum::getValues() ? ['everyday'] : $row['applicableDays'];

            foreach ($applicableDays as $day) {
                if (empty($schedule[$day])) {
                    $schedule[$day] = [];
                }

                $schedule[$day][] = ['startTime' => $row['startTime'], 'endTime' => $row['endTime']];
            }
        }

        // Flush the last pending view.
        if ($currentMeasure['fromLongitude'] !== null && $currentMeasure['toLongitude'] !== null) {
            $address = LocationAddress::fromString($currentMeasure['address']);
            if ($address->getRoadName() !== null) {
                $incidentViews[] = self::makeIncidentView($currentMeasure, $address, $schedule);
            }
        }

        return $incidentViews;
    }
}
