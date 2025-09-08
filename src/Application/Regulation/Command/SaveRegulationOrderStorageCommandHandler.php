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
            $path = $command->path;
            if ($command->file !== null) {
                if ($path !== null) {
                    $this->storage->delete($command->path);
                }
                $path = $this->storage->write($folder, $command->file);
            } elseif ($command->url !== null && $command->path !== null) {
                // Switching from a previously stored file to a URL: delete file and clear path
                $this->storage->delete($command->path);
                $path = null;
            }

            $storageRegulationOrder->update(
                path: $path,
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
