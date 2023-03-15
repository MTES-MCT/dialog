<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

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
        $regulations = $this->repository->findRegulationsByOrganization(
            $query->organization,
            $query->pageSize,
            $query->page,
            $query->status,
        );
        $totalItems = $this->repository->countRegulationsByOrganization(
            $query->organization,
            $query->status,
        );
        $regulationOrderViews = [];

        foreach ($regulations as $regulation) {
            $regulationOrderViews[] = new RegulationOrderListItemView(
                $regulation['uuid'],
                $regulation['status'],
                $regulation['startDate'],
                $regulation['endDate'],
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
