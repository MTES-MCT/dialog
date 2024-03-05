<?php

declare(strict_types=1);

namespace App\Application;

interface RoadLineSectionMakerInterface
{
    public function computeRoadLineSection(
        RoadLine $roadLine,
        string|null $fromHouseNumber,
        string|null $fromRoadName,
        string|null $toHouseNumber,
        string|null $toRoadName,
    ): string;
}
