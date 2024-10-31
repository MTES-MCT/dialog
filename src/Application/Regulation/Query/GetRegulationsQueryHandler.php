<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\NamedStreetView;
use App\Application\Regulation\View\NumberedRoadView;
use App\Application\Regulation\View\RawGeoJSONView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Pagination;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
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

        $rows = $this->repository->findAllRegulations($query->dto);

        foreach ($rows['items'] as $row) {
            $locationView = null;

            if ($row['namedStreet']) {
                [$roadName, $cityLabel, $cityCode] = explode('#', $row['namedStreet']);
                $locationView = new NamedStreetView($cityCode, $cityLabel, $roadName);
            } elseif ($row['numberedRoad']) {
                [$roadNumber, $administrator] = explode('#', $row['numberedRoad']);
                $locationView = new NumberedRoadView($roadNumber, $administrator);
            } elseif ($row['rawGeoJSON']) {
                $label = $row['rawGeoJSON'];
                $locationView = new RawGeoJSONView($label);
            }

            $startDate = $row['overallStartDate'] ? new \DateTimeImmutable($row['overallStartDate']) : null;
            $endDate = null;

            // Returning overallEndDate = NULL for permanent regulation orders is too complex in SQL, we do it here in PHP.
            if ($row['overallEndDate'] && $row['category'] !== RegulationOrderCategoryEnum::PERMANENT_REGULATION->value) {
                $endDate = new \DateTimeImmutable($row['overallEndDate']);
            }

            $regulationOrderViews[] = new RegulationOrderListItemView(
                uuid: $row['uuid'],
                identifier: $row['identifier'],
                status: $row['status'],
                numLocations: $row['nbLocations'],
                organizationName: $row['organizationName'],
                organizationUuid: $row['organizationUuid'],
                location: $locationView,
                startDate: $startDate,
                endDate: $endDate,
            );
        }

        return new Pagination(
            $regulationOrderViews,
            $rows['count'],
            $query->dto->page,
            $query->dto->pageSize,
        );
    }
}
