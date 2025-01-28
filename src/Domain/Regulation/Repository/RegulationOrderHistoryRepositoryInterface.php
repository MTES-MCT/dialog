<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrderHistory;

interface RegulationOrderHistoryRepositoryInterface
{
    public function add(RegulationOrderHistory $regulationOrderHistory): RegulationOrderHistory;

    public function findLastRegulationOrderHistoriesByRegulationOrderUuid(string $regulationOrderUuid): array;
}
