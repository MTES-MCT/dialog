<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\LocationView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Pagination;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetRegulationsQuery $query): Pagination
    {
        $regulationOrderRecords = $this->repository->findRegulationsByOrganization(
            $query->organization,
            $query->pageSize,
            $query->page,
            $query->isPermanent,
        );

        $totalItems = $this->repository->countRegulationsByOrganization(
            $query->organization,
            $query->isPermanent,
        );
        $regulationOrderViews = [];

        foreach ($regulationOrderRecords as $regulationOrderRecord) {
            $regulationOrder = $regulationOrderRecord->getRegulationOrder();
            $locations = $regulationOrder->getLocations();
            $nbLocations = $locations->count();

            $regulationOrderViews[] = new RegulationOrderListItemView(
                uuid: $regulationOrderRecord->getUuid(),
                identifier: $regulationOrder->getIdentifier(),
                status: $regulationOrderRecord->getStatus(),
                nbMoreLocations: $nbLocations > 1 ? $nbLocations - 1 : 0,
                location: $nbLocations ? new LocationView(
                    LocationAddress::fromString($locations->first()->getAddress()),
                ) : null,
                startDate: $regulationOrder->getStartDate(),
                endDate: $regulationOrder->getEndDate(),
            );
        }

        return new Pagination(
            $regulationOrderViews,
            $totalItems,
            $query->page,
            $query->pageSize,
        );
    }
}
