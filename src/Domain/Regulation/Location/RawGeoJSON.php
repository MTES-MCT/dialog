<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

class RawGeoJSON
{
    public function __construct(
        private string $uuid,
        private Location $location,
        private string $label,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function update(
        string $label,
    ): void {
        $this->label = $label;
    }
}
