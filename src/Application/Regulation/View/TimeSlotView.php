<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

readonly class TimeSlotView
{
    public function __construct(
        public ?\DateTimeInterface $startTime,
        public ?\DateTimeInterface $endTime,
    ) {
    }
}
