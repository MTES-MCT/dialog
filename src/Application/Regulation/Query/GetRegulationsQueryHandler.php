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
        $regulations = $this->repository->findRegulationsByOrganization(
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

        foreach ($regulations as $regulation) {
            $regulationOrderViews[] = new RegulationOrderListItemView(
                $regulation['uuid'],
                $regulation['identifier'],
                $regulation['status'],
                $regulation['address'] ? new LocationView(
                    LocationAddress::fromString($regulation['address']),
                ) : null,
                $regulation['startDate'],
                $regulation['endDate'],
                $regulation['email'],
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
