<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Regulation\Measure;

class Period
{
    public function __construct(
        private string $uuid,
        private Measure $measure,
        private ?\DateTimeInterface $startDateTime,
        private ?\DateTimeInterface $endDateTime,
        private ?string $recurrenceType,
        private ?DailyRange $dailyRange = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStartDateTime(): ?\DateTimeInterface
    {
        return $this->startDateTime;
    }

    public function getEndDateTime(): ?\DateTimeInterface
    {
        return $this->endDateTime;
    }

    public function getRecurrenceType(): ?string
    {
        return $this->recurrenceType;
    }

    public function getMeasure(): Measure
    {
        return $this->measure;
    }

    public function getDailyRange(): ?DailyRange
    {
        return $this->dailyRange;
    }

    public function setDailyRange(?DailyRange $dailyRange): void
    {
        $this->dailyRange = $dailyRange;
    }

    public function update(
        \DateTimeInterface $startDateTime,
        \DateTimeInterface $endDateTime,
        string $recurrenceType,
    ): void {
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->recurrenceType = $recurrenceType;
    }
}
