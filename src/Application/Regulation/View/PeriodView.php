<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class PeriodView
{
    public function __construct(
        public readonly \DateTimeInterface $startPeriod,
        public readonly ?\DateTimeInterface $endPeriod = null,
    ) {
    }
}
