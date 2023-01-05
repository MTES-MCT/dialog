<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderListForDatexFormatView;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

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
