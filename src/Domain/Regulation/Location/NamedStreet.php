<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

use App\Domain\Regulation\Enum\PointTypeEnum;

class NamedStreet
{
    public function __construct(
        private string $uuid,
        private Location $location,
        private string $direction,
        private ?string $cityCode = null,
        private ?string $cityLabel = null,
        private ?string $roadBanId = null,
        private ?string $roadName = null,
        private ?string $fromHouseNumber = null,
        private ?string $fromRoadName = null,
        private ?string $toHouseNumber = null,
        private ?string $toRoadName = null,
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

    public function getRoadBanId(): ?string
    {
        return $this->roadBanId;
    }

    public function getRoadName(): ?string
    {
        return $this->roadName;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getFromPointType(): ?string
    {
        if ($this->fromHouseNumber) {
            return PointTypeEnum::HOUSE_NUMBER->value;
        }

        if ($this->fromRoadName) {
            return PointTypeEnum::INTERSECTION->value;
        }

        return null;
    }

    public function getFromHouseNumber(): ?string
    {
        return $this->fromHouseNumber;
    }

    public function getFromRoadName(): ?string
    {
        return $this->fromRoadName;
    }

    public function getToPointType(): ?string
    {
        if ($this->toHouseNumber) {
            return PointTypeEnum::HOUSE_NUMBER->value;
        }

        if ($this->toRoadName) {
            return PointTypeEnum::INTERSECTION->value;
        }

        return null;
    }

    public function getToHouseNumber(): ?string
    {
        return $this->toHouseNumber;
    }

    public function getToRoadName(): ?string
    {
        return $this->toRoadName;
    }

    public function update(
        string $direction,
        ?string $cityCode = null,
        ?string $cityLabel = null,
        ?string $roadBanId = null,
        ?string $roadName = null,
        ?string $fromHouseNumber = null,
        ?string $fromRoadName = null,
        ?string $toHouseNumber = null,
        ?string $toRoadName = null,
    ): void {
        $this->direction = $direction;
        $this->cityCode = $cityCode;
        $this->cityLabel = $cityLabel;
        $this->roadBanId = $roadBanId;
        $this->roadName = $roadName;
        $this->fromHouseNumber = $fromHouseNumber;
        $this->fromRoadName = $fromRoadName;
        $this->toHouseNumber = $toHouseNumber;
        $this->toRoadName = $toRoadName;
    }
}
