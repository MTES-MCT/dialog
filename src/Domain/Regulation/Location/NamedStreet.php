<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

class NamedStreet
{
    public function __construct(
        private string $uuid,
        private Location $location,
        private ?string $cityCode = null,
        private ?string $cityLabel = null,
        private ?string $roadName = null,
        private ?string $fromHouseNumber = null,
        private ?string $toHouseNumber = null,
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

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    public function getCityLabel(): ?string
    {
        return $this->cityLabel;
    }

    public function getRoadName(): ?string
    {
        return $this->roadName;
    }

    public function getFromHouseNumber(): ?string
    {
        return $this->fromHouseNumber;
    }

    public function getToHouseNumber(): ?string
    {
        return $this->toHouseNumber;
    }

    public function update(
        ?string $cityCode = null,
        ?string $cityLabel = null,
        ?string $roadName = null,
        ?string $fromHouseNumber = null,
        ?string $toHouseNumber = null,
    ): void {
        $this->cityCode = $cityCode;
        $this->cityLabel = $cityLabel;
        $this->roadName = $roadName;
        $this->fromHouseNumber = $fromHouseNumber;
        $this->toHouseNumber = $toHouseNumber;
    }
}
