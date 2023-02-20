<?php

declare(strict_types=1);

namespace App\Domain\Condition\Location;

class SupplementaryPositionalDescription
{
    private array $roadInformations = [];

    public function __construct(
        private string $uuid,
        private Location $location,
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

    public function getRoadInformations(): array
    {
        return $this->roadInformations;
    }
}
