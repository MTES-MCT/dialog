<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface GeocoderInterface
{
    public function computeCoordinates(string $address): Coordinates;

    public function computeJunctionCoordinates(string $address, string $roadName): Coordinates;

    public function findAddresses(string $search): array;
}
