<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class RegulationOrderListForDatexFormatView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $description,
        public readonly string $issuingAuthority,
        public readonly PeriodView $period,
    ) {
    }
}
