<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class LocationNew
{
    public function __construct(
        private string $uuid,
        private Measure $measure,
        private string $cityCode,
        private string $cityLabel,
        private ?string $roadName,
        private ?string $fromHouseNumber,
        private ?string $toHouseNumber,
        private ?string $geometry,
    ) {
    }

    public static function fromLocation(string $uuid, Measure $measure, Location $location): self
    {
        return new self(
            uuid: $uuid,
            measure: $measure,
            cityLabel: $location->getCityLabel(),
            cityCode: $location->getCityCode(),
            roadName: $location->getRoadName(),
            fromHouseNumber: $location->getFromHouseNumber(),
            toHouseNumber: $location->getToHouseNumber(),
            geometry: $location->getGeometry(),
        );
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getMeasure(): Measure
    {
        return $this->measure;
    }

    public function getCityCode(): string
    {
        return $this->cityCode;
    }

    public function getCityLabel(): string
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

    public function getGeometry(): ?string
    {
        return $this->geometry;
    }

    public function getToHouseNumber(): ?string
    {
        return $this->toHouseNumber;
    }

    public function update(
        string $cityCode,
        string $cityLabel,
        ?string $roadName,
        ?string $fromHouseNumber,
        ?string $toHouseNumber,
        ?string $geometry,
    ): void {
        $this->cityCode = $cityCode;
        $this->cityLabel = $cityLabel;
        $this->roadName = $roadName;
        $this->fromHouseNumber = $fromHouseNumber;
        $this->toHouseNumber = $toHouseNumber;
        $this->geometry = $geometry;
    }
}
