<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface GeocoderInterface
{
    public function computeCoordinates(string $address, ?string $postalCodeHint = null): Coordinates;

    public function findAddresses(string $search): array;
}
