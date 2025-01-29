<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;

final class GetRegulationOrderHistoryQueryHandler
{
    public function __construct(
        private RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderHistoryQuery $query): array
    {
        $regulationOrderHistory = $this->regulationOrderHistoryRepository->findLastRegulationOrderHistoriesByRegulationOrderUuid($query->regulationOrderUuid);

        return $regulationOrderHistory;
    }
}
