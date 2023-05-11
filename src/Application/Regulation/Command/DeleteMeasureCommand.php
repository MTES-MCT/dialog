<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Measure;

final class DeleteMeasureCommand implements CommandInterface
{
    public function __construct(
        public readonly Measure $measure,
    ) {
    }
}
