<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Location;
use App\Domain\Regulation\LocationAddress;

class DetailLocationView
{
    public function __construct(
        public readonly string $uuid,
        public readonly LocationAddress $address,
        public readonly ?string $fromHouseNumber,
        public readonly ?string $toHouseNumber,
    ) {
    }

    public static function fromEntity(Location $location): self
    {
        return new self(
            uuid: $location->getUuid(),
            address: LocationAddress::fromString($location->getAddress()),
            fromHouseNumber: $location->getFromHouseNumber(),
            toHouseNumber: $location->getToHouseNumber(),
        );
    }
}
