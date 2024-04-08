<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface RoadGeocoderInterface
{
    public function computeRoadLine(string $roadName, string $inseeCode): string;

    public function findDepartmentalRoads(string $search, string $administrator): array;

    public function computeRoad(string $roadNumber, string $administrator): string;

    public function computeReferencePoint(
        string $lineGeometry,
        string $administrator,
        string $roadNumber,
        string $pointNumber,
        string $side,
        ?int $abscissa,
    ): Coordinates;
}
