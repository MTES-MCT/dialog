<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Factory;

use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrder;

class LocationFactory
{
    public static function duplicate(
        string $uuid,
        RegulationOrder $regulationOrder,
        Location $location,
    ): Location {
        return new Location(
            uuid: $uuid,
            regulationOrder: $regulationOrder,
            address: $location->getAddress(),
            fromHouseNumber: $location->getFromHouseNumber(),
            fromPoint: $location->getFromPoint(),
            toHouseNumber: $location->getToHouseNumber(),
            toPoint: $location->getToPoint(),
        );
    }
}
