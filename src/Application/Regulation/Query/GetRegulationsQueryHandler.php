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
        $regulationOrderRecords = $this->repository->findRegulationsByOrganizations(
            $query->organizationUuids,
            $query->pageSize,
            $query->page,
            $query->isPermanent,
        );
        $regulationOrderViews = [];

        foreach ($regulationOrderRecords['items'] as $regulationOrderRecord) {
            $regulationOrder = $regulationOrderRecord->getRegulationOrder();
            $locations = $regulationOrder->getLocations();

            $location = $locations->first();
            $nbLocations = $locations->count();
            $locationView = null;

            if ($nbLocations && !$location->getMeasures()->isEmpty()) {
                $measure = $location->getMeasures()->first();
                $locationNew = $measure->getLocations()->first();
                $locationView = new LocationView(
                    $locationNew->getCityCode(),
                    $locationNew->getCityLabel(),
                    $locationNew->getRoadName(),
                );
            }
            $regulationOrderViews[] = new RegulationOrderListItemView(
                uuid: $regulationOrderRecord->getUuid(),
                identifier: $regulationOrder->getIdentifier(),
                status: $regulationOrderRecord->getStatus(),
                numLocations: $nbLocations,
                organizationName: $regulationOrderRecord->getOrganization()->getName(),
                location: $locationView,
                startDate: $regulationOrder->getStartDate(),
                endDate: $regulationOrder->getEndDate(),
            );
        }

        return new Pagination(
            $regulationOrderViews,
            $regulationOrderRecords['count'],
            $query->page,
            $query->pageSize,
        );
    }
}
