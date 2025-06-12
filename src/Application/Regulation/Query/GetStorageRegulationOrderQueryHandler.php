<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;
use App\Domain\Regulation\StorageRegulationOrder;

final class GetStorageRegulationOrderQueryHandler
{
    public function __construct(
        private readonly StorageRegulationOrderRepositoryInterface $storageRegulationOrderRepository,
    ) {
    }

    public function __invoke(GetStorageRegulationOrderQuery $query): StorageRegulationOrder
    {
        $regulationOrderUuid = $query->regulationOrder->getUuid();

        return $this->storageRegulationOrderRepository->findOneByRegulationOrderUuid($regulationOrderUuid);
    }
}
