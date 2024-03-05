<?php

declare(strict_types=1);

namespace App\Application;

interface RoadLineSectionMakerInterface
{
    public function computeRoadLineSection(
        RoadLine $roadLine,
        ?string $fromHouseNumber,
        ?string $fromRoadName,
        ?string $toHouseNumber,
        ?string $toRoadName,
    ): string;
}
