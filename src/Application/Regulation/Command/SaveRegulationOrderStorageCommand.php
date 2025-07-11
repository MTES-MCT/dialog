<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\StorageRegulationOrder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SaveRegulationOrderStorageCommand implements CommandInterface
{
    public ?UploadedFile $file = null;
    public ?string $path = null;
    public ?string $url = null;
    public ?string $title = null;

    public function __construct(
        public readonly RegulationOrder $regulationOrder,
        public readonly ?StorageRegulationOrder $storageRegulationOrder = null,
    ) {
        $this->path = $storageRegulationOrder?->getPath();
        $this->url = $storageRegulationOrder?->getUrl();
        $this->title = $storageRegulationOrder?->getTitle();
    }
}
