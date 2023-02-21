<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

class PeriodView
{
    public function __construct(
        public readonly \DateTimeInterface $startDate,
        public readonly ?\DateTimeInterface $startTime = null,
        public readonly ?\DateTimeInterface $endDate = null,
        public readonly ?\DateTimeInterface $endTime = null,
    ) {
    }
}
