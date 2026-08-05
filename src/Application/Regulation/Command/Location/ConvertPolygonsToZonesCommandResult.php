<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

final readonly class ConvertPolygonsToZonesCommandResult
{
    public function __construct(
        public int $numLocations,
        public array $convertedLocationUuids,
        public array $exceptions,
    ) {
    }
}
