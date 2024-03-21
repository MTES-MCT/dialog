<?php

declare(strict_types=1);

namespace App\Application;

interface DepartmentalRoadSectionMakerInterface
{
    public function computeSection(
        string $fullDepartmentalRoadGeometry,
        string $administrator,
        string $roadNumber,
        string $fromPointNumber,
        string $fromSide,
        int $fromAbscissa,
        string $toPointNumber,
        string $toSide,
        int $toAbscissa,
    ): string;
}
