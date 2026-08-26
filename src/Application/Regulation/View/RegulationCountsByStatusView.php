<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class RegulationCountsByStatusView
{
    public function __construct(
        public int $draftCount,
        public int $currentCount,
        public int $upcomingCount,
    ) {
    }
}
