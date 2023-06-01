<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

class PeriodView
{
    public function __construct(
        public readonly array $applicableDays,
        public readonly \DateTimeInterface $startTime,
        public readonly \DateTimeInterface $endTime,
    ) {
    }
}
