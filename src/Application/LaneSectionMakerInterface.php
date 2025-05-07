<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface LaneSectionMakerInterface
{
    public function computeSection(
        string $fullLaneGeometry,
        string $roadBanId,
        string $roadName,
        string $cityCode,
        string $direction,
        ?Coordinates $fromCoords,
        ?string $fromHouseNumber,
        ?string $fromRoadBanId,
        ?Coordinates $toCoords,
        ?string $toHouseNumber,
        ?string $toRoadBanId,
    ): string;
}
