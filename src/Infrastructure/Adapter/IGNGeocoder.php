<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\GeocoderInterface;
use App\Domain\Geography\Coordinates;

final class IGNGeocoder implements GeocoderInterface
{
    public function computeCoordinates(string $address): Coordinates
    {
        return new Coordinates(0, 0); // TODO Call "IGN Géocodage 2.0" API
    }
}
