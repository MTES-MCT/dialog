<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

readonly class PeriodView
{
    public function __construct(
        public string $recurrenceType,
        public \DateTimeInterface $startDateTime,
        public ?\DateTimeInterface $endDateTime,
        public ?DailyRangeView $dailyRange,
        public ?array $timeSlots,
    ) {
    }
}
