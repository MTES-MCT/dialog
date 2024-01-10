<?php

declare(strict_types=1);

namespace App\Application;

interface RoadGeocoderInterface
{
    public function computeRoadLine(string $roadName, string $inseeCode): string;
}
