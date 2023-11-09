<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

class TimeSlot
{
    public function __construct(
        private string $uuid,
        private Period $period,
        private \DateTimeInterface $startTime,
        private \DateTimeInterface $endTime,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getPeriod(): Period
    {
        return $this->period;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function update(\DateTimeInterface $startTime, \DateTimeInterface $endTime): void
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }
}
