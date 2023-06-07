<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Regulation\Measure;

class Period
{
    public function __construct(
        private string $uuid,
        private Measure $measure,
        private bool $includeHolidays,
        private array $applicableDays,
        private \DateTimeInterface $startTime,
        private \DateTimeInterface $endTime,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getApplicableDays(): array
    {
        return $this->applicableDays;
    }

    public function isIncludeHolidays(): bool
    {
        return $this->includeHolidays;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function getMeasure(): Measure
    {
        return $this->measure;
    }

    public function update(
        bool $includeHolidays,
        array $applicableDays,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
    ): void {
        $this->includeHolidays = $includeHolidays;
        $this->applicableDays = $applicableDays;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }
}
