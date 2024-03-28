<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Condition\Period;

final class DatexValidityConditionView
{
    public function __construct(
        public readonly \DateTimeInterface $overallStartTime,
        public readonly ?\DateTimeInterface $overallEndTime,
        public readonly array $validPeriods,
    ) {
    }

    /** @return self[] */
    public static function fromPeriods(/* @var Period[] */ array $periods): array
    {
        $validityConditions = [];

        foreach ($periods as $period) {
            $overallStartTime = $period->getStartDateTime();
            $overallEndTime = $period->getEndDateTime();

            $validPeriods = [];

            if ($period->getDailyRange() || $period->getTimeSlots()) {
                $timePeriods = [];

                foreach ($period->getTimeSlots() ?? [] as $timeSlot) {
                    $timePeriods[] = ['startTime' => $timeSlot->getStartTime(), 'endTime' => $timeSlot->getEndTime()];
                }

                $validPeriods[] = [
                    'timePeriods' => $timePeriods,
                    'dayWeekMonthPeriods' => $period->getDailyRange() ? [$period->getDailyRange()->getApplicableDays()] : [],
                ];
            }

            $validityConditions[] = new self(
                $overallStartTime,
                $overallEndTime,
                $validPeriods,
            );
        }

        return $validityConditions;
    }
}
