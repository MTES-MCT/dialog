<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Query;

use App\Application\RegulationOrder\View\PeriodView;
use App\Application\RegulationOrder\View\RegulationOrderListItemView;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;

final class GetAllRegulationOrderListItemsQueryHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetAllRegulationOrderListItemsQuery $query): array
    {
        $regulationOrders = $this->repository->findRegulationOrders();
        $regulationOrderViews = [];

        foreach ($regulationOrders as $regulationOrder) {
            $regulationOrderViews[] = new RegulationOrderListItemView(
                $regulationOrder['uuid'],
                $regulationOrder['issuingAuthority'],
                new PeriodView(
                    $regulationOrder['startPeriod'],
                    $regulationOrder['endPeriod'],
                ),
            );
        }

        return $regulationOrderViews;
    }
}
