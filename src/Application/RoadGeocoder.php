<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface RoadGeocoderInterface
{
    public function computeRoadLine(string $roadName, string $inseeCode): string;
}
