<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\CifsIncidentView;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrdersAsCifsIncidentsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
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

        $incidentViews = [];

        foreach ($rows as $row) {
            $address = LocationAddress::fromString($row['address']);

            if ($address->getRoadName() === null) {
                continue;
            }

            $incidentViews[] = new CifsIncidentView(
                id: $row['measureId'],
                creationTime: $row['createdAt']->format('Y-m-d\TH:i:s'),
                type: 'ROAD_CLOSED',
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
            );
        }

        return $incidentViews;
    }
}
