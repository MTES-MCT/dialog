<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\StorageInterface;
use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;

final class DeleteRegulationOrderStorageCommandHandler
{
    public function __construct(
        private StorageInterface $storage,
        private readonly StorageRegulationOrderRepositoryInterface $storageRegulationOrderRepository,
    ) {
    }

    public function __invoke(DeleteRegulationOrderStorageCommand $command): void
    {
        $regulationOrderUuid = $command->regulationOrder->getUuid();
        $storageRegulationOrder = $this->storageRegulationOrderRepository->findOneByRegulationOrderUuid($regulationOrderUuid);

        if (!$storageRegulationOrder) {
            return;
        }

        $path = $storageRegulationOrder->getPath();
        $url = $storageRegulationOrder->getUrl();

        if ($path !== null && $url !== null) {
            $this->storage->delete($path);
            $storageRegulationOrder->setPath(null);
        }
        if ($path !== null && $url === null || $path === null && $url !== null) {
            $this->storageRegulationOrderRepository->remove($storageRegulationOrder);
        }
    }
}
