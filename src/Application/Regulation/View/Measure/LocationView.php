<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class LocationView
{
    public function __construct(
        public string $uuid,
        public string $roadType,
        public ?NamedStreetView $namedStreet = null,
        public ?NumberedRoadView $numberedRoad = null,
    ) {
    }
}
