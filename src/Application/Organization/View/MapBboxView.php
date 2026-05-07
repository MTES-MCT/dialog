<?php

declare(strict_types=1);

namespace App\Application\Organization\View;

final readonly class MapBboxView
{
    public function __construct(
        public float $minLon,
        public float $minLat,
        public float $maxLon,
        public float $maxLat,
    ) {
    }
}
