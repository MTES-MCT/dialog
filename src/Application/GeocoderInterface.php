<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface GeocoderInterface
{
    public function computeCoordinates(string $address, string $cityCode): Coordinates;

    public function findCities(string $search): array;
}
