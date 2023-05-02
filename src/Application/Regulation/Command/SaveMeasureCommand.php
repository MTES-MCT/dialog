<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;

    public function __construct(
        public readonly Location $location,
        public readonly ?Measure $measure = null,
    ) {
    }

    public static function create(
        Location $location,
        Measure $measure = null,
    ): self
    {
        $command = new self($location, $measure);
        $command->type = $measure->type;
        return $command;
    }
}
