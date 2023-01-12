<?php

declare(strict_types=1);

namespace App\Domain\Geography;

class Coordinates
{
    public function __construct(
        private float $latitude,
        private float $longitude,
    ) {
    }

    public static function fromLatLon(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }
}
