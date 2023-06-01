<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Condition;

use App\Application\CommandInterface;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;

final class SavePeriodCommand implements CommandInterface
{
    public ?array $applicableDays;
    public ?\DateTimeInterface $startTime;
    public ?\DateTimeInterface $endTime;
    public ?bool $includeHolidays;
    public ?Measure $measure;

    public function __construct(
        public readonly ?Period $period = null,
    ) {
        $this->applicableDays = $period?->getApplicableDays();
        $this->startTime = $period?->getStartTime();
        $this->endTime = $period?->getEndTime();
        $this->includeHolidays = $period?->isIncludeHolidays();
    }
}
