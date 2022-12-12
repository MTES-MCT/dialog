<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Query;

use App\Application\RegulationOrder\View\PeriodView;
use App\Application\RegulationOrder\View\RegulationOrderListForDatexFormatView;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;

final class GetRegulationOrdersToDatexFormatQueryHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetRegulationOrdersToDatexFormatQuery $query): array
    {
        $regulationOrders = $this->repository->findRegulationOrdersForDatexFormat();
        $regulationOrderViews = [];

        foreach ($regulationOrders as $regulationOrder) {
            $regulationOrderViews[] = new RegulationOrderListForDatexFormatView(
                $regulationOrder['uuid'],
                $regulationOrder['description'],
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
