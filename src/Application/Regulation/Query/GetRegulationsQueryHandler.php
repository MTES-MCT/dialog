<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\NamedStreetView;
use App\Application\Regulation\View\NumberedRoadView;
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

        $rows = $this->repository->findAllRegulations(
            $query->pageSize,
            $query->page,
            $query->listFiltersDTO,
        );

        foreach ($rows['items'] as $row) {
            $locationView = null;

            if ($row['namedStreet']) {
                [$roadName, $cityLabel, $cityCode] = explode('#', $row['namedStreet']);
                $locationView = new NamedStreetView($cityCode, $cityLabel, $roadName);
            } elseif ($row['numberedRoad']) {
                [$roadNumber, $administrator] = explode('#', $row['numberedRoad']);
                $locationView = new NumberedRoadView($roadNumber, $administrator);
            }

            $regulationOrderViews[] = new RegulationOrderListItemView(
                uuid: $row['uuid'],
                identifier: $row['identifier'],
                status: $row['status'],
                numLocations: $row['nbLocations'],
                organizationName: $row['organizationName'],
                organizationUuid: $row['organizationUuid'],
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
