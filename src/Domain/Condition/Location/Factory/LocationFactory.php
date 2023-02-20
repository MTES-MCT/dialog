<?php

declare(strict_types=1);

namespace App\Domain\Condition\Location\Factory;

use App\Domain\Condition\Location\Location;
use App\Domain\Condition\RegulationCondition;

class LocationFactory
{
    public static function duplicate(
        string $uuid,
        RegulationCondition $regulationCondition,
        Location $location,
    ): Location {
        return new Location(
            uuid: $uuid,
            regulationCondition: $regulationCondition,
            postalCode: $location->getPostalCode(),
            city: $location->getCity(),
            roadName: $location->getRoadName(),
            fromHouseNumber: $location->getFromHouseNumber(),
            fromPoint: $location->getFromPoint(),
            toHouseNumber: $location->getToHouseNumber(),
            toPoint: $location->getToPoint(),
        );
    }
}
