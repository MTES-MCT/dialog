<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\StorageRegulationOrder;

interface StorageRegulationOrderRepositoryInterface
{
    public function add(StorageRegulationOrder $storageRegulationOrder): StorageRegulationOrder;

    public function findOneByRegulationOrderUuid(string $uuid): ?StorageRegulationOrder;

    public function remove(StorageRegulationOrder $storageRegulationOrder): void;
}
