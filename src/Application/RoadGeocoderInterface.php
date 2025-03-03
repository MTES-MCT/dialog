<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface RoadGeocoderInterface
{
    public const HIGHWAY = 'HIGHWAY';

    public function computeRoadLine(string $roadName, string $inseeCode): string;

    public function findRoads(string $search, string $roadType, string $administrator): array;

    public function computeRoad(string $roadType, string $administrator, string $roadNumber): string;

    public function findReferencePoints(string $search, string $administrator, string $roadNumber): array;

    public function computeReferencePoint(
        string $roadType,
        string $administrator,
        string $roadNumber,
        ?string $departmentCode,
        string $pointNumber,
        string $side,
        int $abscissa,
    ): Coordinates;

    public function findSides(string $administrator, string $roadNumber, ?string $departmentCode, string $pointNumber): array;

    public function findRoadNames(string $search, string $cityCode): array;

    public function findSectionsInArea(string $areaGeometry, array $excludeTypes = [], ?bool $clipToArea = false): string;

    public function convertPolygonRoadToLines(string $geometry): string;
}
