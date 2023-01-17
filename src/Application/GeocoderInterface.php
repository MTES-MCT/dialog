<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface GeocoderInterface
{
    public function computeCoordinates(
        string $postalCode,
        string $city,
        string $road,
        string $houseNumber,
    ): Coordinates;
}
