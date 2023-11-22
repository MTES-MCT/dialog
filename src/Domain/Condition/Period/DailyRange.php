<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Condition\Period\Enum\ApplicableDayEnum;

class DailyRange
{
    public function __construct(
        private string $uuid,
        private array $applicableDays,
        private Period $period,
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

    public function getPediod(): Period
    {
        return $this->period;
    }

    public function update(array $applicableDays): void
    {
        $this->applicableDays = $applicableDays;
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
}
