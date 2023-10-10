<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
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
        private ?DailyRange $dailyRange = null,
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

    public function getDaysRanges(): array
    {
        $daysRanges = [];
        $days = ApplicableDayEnum::getValues();
        $i = 0;

        foreach ($this->applicableDays as $currentDay) {
            $daysRanges[$i] = ['firstDay' => $currentDay, 'lastDay' => $currentDay];

            if ($i > 0) {
                $previousDay = $daysRanges[$i - 1]['lastDay'];
                $previousDayKey = array_search($previousDay, $days);
                $currentDayKey = array_search($currentDay, $days);

                if (($currentDayKey - 1) === $previousDayKey) {
                    unset($daysRanges[$i]);
                    $daysRanges[$i - 1]['lastDay'] = $currentDay;

                    continue;
                }
            }

            ++$i;
        }

        return $daysRanges;
    }

    public function getDailyRange(): ?DailyRange
    {
        return $this->dailyRange;
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
