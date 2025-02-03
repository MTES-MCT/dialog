<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\RegulationOrderHistoryView;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;

final class GetRegulationOrderHistoryQueryHandler
{
    public function __construct(
        private RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderHistoryQuery $query): ?RegulationOrderHistoryView
    {
        $row = $this->regulationOrderHistoryRepository->findLastRegulationOrderHistoryByUuid($query->regulationOrderUuid);

        $date = $row['date'];

        $regulationOrderHistoryView = new RegulationOrderHistoryView(
            date: $date->format('d-m-Y'),
            action: $row['action'],
        );

        return $regulationOrderHistoryView;
    }
}
