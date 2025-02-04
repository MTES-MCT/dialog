<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class RegulationOrderHistoryView
{
    public function __construct(
        public readonly \DateTimeInterface $date,
        public readonly string $action,
    ) {
    }
}
