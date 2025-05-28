<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\StorageRegulationOrder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SaveRegulationOrderStorageCommand implements CommandInterface
{
    public ?UploadedFile $file;
    public ?string $path = null;
    public ?string $url = null;

    public function __construct(
        public readonly RegulationOrder $regulationOrder,
        public readonly ?StorageRegulationOrder $storageRegulationOrder = null,
    ) {
    }

    public static function create(
        ?StorageRegulationOrder $storageRegulationOrder = null,
    ): self {
        $command = new self($storageRegulationOrder);
        $command->regulationOrder = $regulationOrder;
        $command->path = $storageRegulationOrder?->getPath();
        $command->url = $storageRegulationOrder?->getUrl();

        return $command;
    }
}
