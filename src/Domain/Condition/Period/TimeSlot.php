<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

class TimeSlot
{
    public function __construct(
        private string $uuid,
        private DailyRange $dailyRange,
        private \DateTimeInterface $startTime,
        private \DateTimeInterface $endTime,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getDailyRange(): DailyRange
    {
        return $this->dailyRange;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }
}
