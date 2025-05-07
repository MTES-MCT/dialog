<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface IntersectionGeocoderInterface
{
    public function findIntersectingNamedStreets(string $roadBanId): array;

    public function computeIntersection(string $roadName, string $otherRoadName, string $cityCode): Coordinates;
}
