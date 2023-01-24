<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class RegulationOrderListItemView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $status,
        public readonly ?ListItemLocationView $location,
        public readonly ?PeriodView $period,
    ) {
    }
}
