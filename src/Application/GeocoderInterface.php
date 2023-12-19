<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface GeocoderInterface
{
    public function computeCoordinates(string $address, string $cityCode): Coordinates;

    public function computeJunctionCoordinates(string $address, string $roadName, string $cityCode): Coordinates;

    public function findRoadNames(string $search, string $cityCode): array;

    public function findCities(string $search): array;
}
