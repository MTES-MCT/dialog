<?php

declare(strict_types=1);

namespace App\Application;

interface RoadSectionMakerInterface
{
    public function computeSection(
        string $fullRoadGeometry,
        string $roadType,
        string $administrator,
        string $roadNumber,
        ?string $fromDepartmentCode,
        string $fromPointNumber,
        string $fromSide,
        int $fromAbscissa,
        ?string $toDepartmentCode,
        string $toPointNumber,
        string $toSide,
        int $toAbscissa,
        string $direction,
    ): string;
}
