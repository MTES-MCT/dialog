<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

class TimePeriodOfDay
{
    public function __construct(
        private string $uuid,
        private \DateTimeImmutable $startTime,
        private \DateTimeImmutable $endTime,
        private Period $period,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function getPeriod(): Period
    {
        return $this->period;
    }
}
