<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;

interface LineSectionMakerInterface
{
    public function computeSection(
        RoadTypeEnum $roadType,
        string $geometry,
        Coordinates $fromCoords,
        Coordinates $toCoords,
    ): string;
}
