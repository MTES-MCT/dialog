<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class Location
{
    private iterable $measures = [];

    public function __construct(
        private string $uuid,
        private RegulationOrder $regulationOrder,
        private string $postalCode,
        private string $city,
        private string $roadName,
        private ?string $fromHouseNumber,
        private ?string $fromPoint,
        private ?string $toHouseNumber,
        private ?string $toPoint,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getRoadName(): string
    {
        return $this->roadName;
    }

    public function getFromHouseNumber(): ?string
    {
        return $this->fromHouseNumber;
    }

    public function getFromPoint(): ?string
    {
        return $this->fromPoint;
    }

    public function getToHouseNumber(): ?string
    {
        return $this->toHouseNumber;
    }

    public function getToPoint(): ?string
    {
        return $this->toPoint;
    }

    public function getMeasures(): iterable
    {
        return $this->measures;
    }

    public function update(
        string $postalCode,
        string $city,
        string $roadName,
        ?string $fromHouseNumber,
        ?string $fromPoint,
        ?string $toHouseNumber,
        ?string $toPoint,
    ): void {
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->roadName = $roadName;
        $this->fromHouseNumber = $fromHouseNumber;
        $this->fromPoint = $fromPoint;
        $this->toHouseNumber = $toHouseNumber;
        $this->toPoint = $toPoint;
    }
}
