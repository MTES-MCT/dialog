<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class VehicleCharacteristicsView
{
    public function __construct(
        public ?float $maxWeight = null,
        public ?float $maxHeight = null,
        public ?float $maxWidth = null,
        public ?float $maxLength = null,
    ) {
    }
}
