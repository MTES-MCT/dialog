<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\StorageInterface;

final class SaveRegulationOrderStorageCommandHandler
{
    public function __construct(
        private StorageInterface $storage,
    ) {
    }

    public function __invoke(SaveRegulationOrderStorageCommand $command): void
    {
        $folder = \sprintf('regulationOrder/%s', $command->regulationOrder->getUuid());
        $regulationOrder = $command->regulationOrder;

        if ($storageRegulationOrder = $storageRegulationOrder->getPath()) {
            $this->storage->delete($storageRegulationOrder);
        }

        $storageRegulationOrder->setPath($this->storage->write($folder, $command->file));
    }
}
