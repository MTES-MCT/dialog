<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\LocationView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Pagination;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetRegulationsQuery $query): Pagination
    {
        $regulationOrderViews = [];
        $rows = $this->repository->findRegulationsByOrganizations(
            $query->organizationUuids,
            $query->pageSize,
            $query->page,
            $query->isPermanent,
        );

        foreach ($rows['items'] as $row) {
            $locationView = null;

            if ($row['location']) {
                [$roadName, $cityLabel, $cityCode] = explode('#', $row['location']);
                $locationView = new LocationView($cityCode, $cityLabel, $roadName);
            } elseif ($row['departmentalRoad']) {
                [$roadNumber, $administrator] = explode('#', $row['departmentalRoad']);
                $locationView = new LocationView(null, null, null, $roadNumber, $administrator);
            }

            $regulationOrderViews[] = new RegulationOrderListItemView(
                uuid: $row['uuid'],
                identifier: $row['identifier'],
                status: $row['status'],
                numLocations: $row['nbLocations'],
                organizationName: $row['organizationName'],
                location: $locationView,
                startDate: $row['startDate'],
                endDate: $row['endDate'],
            );
        }

        return new Pagination(
            $regulationOrderViews,
            $rows['count'],
            $query->page,
            $query->pageSize,
        );
    }
}
