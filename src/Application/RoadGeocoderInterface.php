<?php

declare(strict_types=1);

namespace App\Application;

interface RoadGeocoderInterface
{
    public function computeRoadLine(string $roadName, string $inseeCode): string;

    public function findDepartmentalRoads(string $search, string $administrator): array;

    public function findReferencePointAbscisse(string $administrator, string $roadNumber, string $direction, int $pointNumber, int $abscissa): int;
}
