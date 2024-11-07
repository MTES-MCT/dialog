<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrderRecord;

final class DuplicateMeasureFragmentCommand implements CommandInterface
{
    public function __construct(
        public Measure $measure,
        public RegulationOrderRecord $regulationOrderRecord,
    ) {
    }
}
