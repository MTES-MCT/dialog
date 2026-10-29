<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

readonly class PeriodView
{
    /**
     * @param \DateTimeInterface[] $excludedPublicHolidays
     */
    public function __construct(
        public string $recurrenceType,
        public ?\DateTimeInterface $startDateTime,
        public ?\DateTimeInterface $endDateTime,
        public ?DailyRangeView $dailyRange,
        public ?array $timeSlots,
        public bool $isPublicHolidaysExcluded = false,
        public array $excludedPublicHolidays = [],
    ) {
    }
}
