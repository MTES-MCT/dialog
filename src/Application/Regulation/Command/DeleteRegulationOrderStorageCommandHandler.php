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
        $storageRegulationOrder = $command->storageRegulationOrder;

        $path = $storageRegulationOrder->getPath();
        if ($path !== null) {
            $this->storage->delete($path);
            $storageRegulationOrder->setPath(null);
        }
        $this->storageRegulationOrderRepository->remove($storageRegulationOrder);
    }
}
