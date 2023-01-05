<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderListItemView;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

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
