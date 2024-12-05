<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface LaneSectionMakerInterface
{
    public function computeSection(
        string $fullLaneGeometry,
        string $roadName,
        string $cityCode,
        string $direction,
        ?Coordinates $fromCoords,
        ?string $fromHouseNumber,
        ?string $fromRoadName,
        ?Coordinates $toCoords,
        ?string $toHouseNumber,
        ?string $toRoadName,
    ): string;
}
