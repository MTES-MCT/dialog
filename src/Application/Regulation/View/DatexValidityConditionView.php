<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class DatexValidityConditionView
{
    public function __construct(
        public \DateTimeInterface $overallStartTime,
        public ?\DateTimeInterface $overallEndTime,
        public array $validPeriods,
    ) {
    }
}
