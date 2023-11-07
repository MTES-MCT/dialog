<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class DailyRange
{
    private Collection $timeSlots;

    public function __construct(
        private string $uuid,
        private array $applicableDays,
        private Period $period,
    ) {
        $this->timeSlots = new ArrayCollection();
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getApplicableDays(): array
    {
        return $this->applicableDays;
    }

    public function getTimeSlots(): iterable
    {
        return $this->timeSlots;
    }

    public function getPediod(): Period
    {
        return $this->period;
    }

    public function addTimeSlot(TimeSlot $timeSlot): void
    {
        if ($this->timeSlots->contains($timeSlot)) {
            return;
        }

        $this->timeSlots[] = $timeSlot;
    }

    public function removeTimeSlot(TimeSlot $timeSlot): void
    {
        if (!$this->timeSlots->contains($timeSlot)) {
            return;
        }

        $this->timeSlots->removeElement($timeSlot);
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
