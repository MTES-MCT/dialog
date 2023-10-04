<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\CommandInterface;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;

final class SavePeriodCommand implements CommandInterface
{
    public ?\DateTimeInterface $startTime;
    public ?\DateTimeInterface $endTime;
    public ?Measure $measure;

    public function __construct(
        public readonly ?Period $period = null,
    ) {
        $this->startTime = $period?->getStartTime();
        $this->endTime = $period?->getEndTime();
    }
}
