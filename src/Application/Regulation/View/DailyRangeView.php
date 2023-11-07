<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

readonly class DailyRangeView
{
    public function __construct(
        public ?array $dayRanges,
        public ?array $timeSlots,
    ) {
    }
}
