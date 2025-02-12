<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

final readonly class GeocodeLocationsWithoutGeometryCommandResult
{
    public function __construct(
        public int $numLocations,
        public array $updatedLocationUuids,
        public array $exceptions,
    ) {
    }
}
