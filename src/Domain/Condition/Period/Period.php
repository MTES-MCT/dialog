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
        private array $applicableDays,
        private ?\DateTimeInterface $startDate,
        private ?\DateTimeInterface $endDate,
        private \DateTimeInterface $startTime,
        private ?\DateTimeInterface $endTime,
        private ?string $recurrenceType,
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function getRecurrenceType(): ?string
    {
        return $this->recurrenceType;
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
        array $applicableDays,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $recurrenceType,
    ): void {
        $this->applicableDays = $applicableDays;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->recurrenceType = $recurrenceType;
    }
}
