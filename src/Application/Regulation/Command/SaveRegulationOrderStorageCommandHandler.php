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
        if ($command->file !== null) {
            $folder = \sprintf('regulationOrder/%s', $command->regulationOrder->getUuid());
        }

        if ($storageRegulationOrder = $command->storageRegulationOrder) {
            $path = null;
            if ($command->file !== null) {
                if ($command->path !== null) {
                    $this->storage->delete($command->path);
                }
                $path = $this->storage->write($folder, $command->file);
            }

            $storageRegulationOrder->update(
                path: $path ?? $command->path,
                url: $command->url,
                title: $command->title,
                fileSize: $command->file?->getSize(),
                mimeType: $command->file?->getMimeType(),
            );

            return;
        }

        $this->storageRegulationOrderRepository->add(
            new StorageRegulationOrder(
                uuid: $this->idFactory->make(),
                regulationOrder: $command->regulationOrder,
                path: $command->file !== null ? $this->storage->write($folder, $command->file) : null,
                url: $command->url,
                title: $command->title,
                fileSize: $command->file?->getSize(),
                mimeType: $command->file?->getMimeType(),
            ),
        );
    }
}
