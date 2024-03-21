<?php

declare(strict_types=1);

namespace App\Application;

interface DepartmentalRoadSectionMakerInterface
{
    public function computeSection(
        string $fullDepartmentalRoadGeometry,
        string $administrator,
        string $roadNumber,
        string $direction,
        array $pointA,
        array $pointB,
    ): string;
}
