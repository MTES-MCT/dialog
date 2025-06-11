<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\StorageInterface;
use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;
use App\Domain\Regulation\StorageRegulationOrder;
use App\Infrastructure\Adapter\IdFactory;

final class SaveRegulationOrderStorageCommandHandler
{
    public function __construct(
        private StorageInterface $storage,
        private StorageRegulationOrderRepositoryInterface $storageRegulationOrderRepository,
        private IdFactory $idFactory,
    ) {
    }

    public function __invoke(SaveRegulationOrderStorageCommand $command): void
    {
        if ($command->file !== null || $command->url !== null) {
            $folder = \sprintf('regulationOrder/%s', $command->regulationOrder->getUuid());

            /* if ($storageRegulationOrder = $command->storageRegulationOrder) {
                $regulationOrderUuid = $command->regulationOrder->getUuid();
                $storageRegulationOrder = $this->storageRegulationOrderRepository->findOneByRegulationOrderUuid($regulationOrderUuid);
                if ($command->file !== null) {
                    $path = $storageRegulationOrder->getPath();
                    $this->storage->delete($path);
                    $storageRegulationOrder->setPath($this->storage->write($folder, $command->file));
                }
                if ($command->url !== null) {
                    $storageRegulationOrder->setUrl($command->url);
                }
            } */

            $this->storageRegulationOrderRepository->add(
                new StorageRegulationOrder(
                    uuid: $this->idFactory->make(),
                    regulationOrder: $command->regulationOrder,
                    path: $command->file !== null ? $this->storage->write($folder, $command->file) : $command->file,
                    url: $command->url,
                ),
            );
        }
    }
}
