<?php

declare(strict_types=1);

namespace App\Domain\Geography;

class Coordinates
{
    private function __construct(
        public readonly float $longitude,
        public readonly float $latitude,
    ) {
    }

    public static function fromLonLat(float $longitude, float $latitude): self
    {
        return new self($longitude, $latitude);
    }

    public function asGeoJSON(bool $includeCrs = false): string
    {
        return GeoJSON::toPoint($this, $includeCrs);
    }
}
