<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrderHistory;

interface RegulationOrderHistoryRepositoryInterface
{
    public function add(RegulationOrderHistory $regulationOrderHistory): RegulationOrderHistory;

    public function findLastRegulationOrderHistoryByUuid(string $regulationOrderUuid): ?array;

    public function findPublicationDatesByRegulationOrderUuids(array $regulationOrderUuids): array;

    public function countCreatedRegulationOrdersByUserUuids(array $userUuids): array;
}
