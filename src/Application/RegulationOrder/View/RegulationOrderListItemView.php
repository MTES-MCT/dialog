<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\View;

final class RegulationOrderListItemView
{
    public function __construct(
        public readonly string $description,
    ) {
    }
}
