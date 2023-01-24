<?php

declare(strict_types=1);

namespace App\Domain\Geography;

class Coordinates
{
    private function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
    ) {
    }

    public static function fromLatLon(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }
}
