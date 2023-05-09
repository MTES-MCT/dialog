<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;
    public ?Location $location;

    public function __construct(
        public readonly ?Measure $measure = null,
    ) {
        $this->location = $measure?->getLocation();
        $this->type = $measure?->getType();
    }
}
