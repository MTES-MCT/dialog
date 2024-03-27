<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface LineSectionMakerInterface
{
    public function computeSection(
        string $geometry,
        Coordinates $fromCoords,
        Coordinates $toCoords,
    ): ?string;
}
