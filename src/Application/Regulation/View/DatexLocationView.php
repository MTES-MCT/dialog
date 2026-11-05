<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class DatexLocationView
{
    public function __construct(
        public readonly string $roadType,
        public readonly ?string $roadName,
        public readonly ?string $roadNumber,
        public readonly ?string $rawGeoJSONLabel,
        public readonly string $geometry,
        public readonly ?string $wholeCityLabel = null,
        public readonly ?string $zoneLabel = null,
        public readonly ?string $referencePoints = null,
        // both / aligned / opposite, relatif à l'ordre des sommets de $geometry (cf. DirectionEnum::toDatexDirection)
        public readonly ?string $direction = null,
    ) {
    }
}
