<?php

declare(strict_types=1);

namespace App\Application;

interface MapGeocoderInterface
{
    public function findPlaces(string $search): array;
}
