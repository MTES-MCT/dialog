<?php

declare(strict_types=1);

namespace App\Domain\Condition;

class VehicleCharacteristics
{
    public function __construct(
        private string $uuid,
        private ?string $vehicleUsage = null,
        private ?string $vehicleType = null,
        private ?string $vehicleCritair = null,
        private ?float $maxWeight = null,
        private ?float $maxHeight = null,
        private ?float $maxWidth = null,
        private ?float $maxLength = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getMaxWeight(): ?float
    {
        return $this->maxWeight;
    }

    public function getMaxHeight(): ?float
    {
        return $this->maxHeight;
    }

    public function getMaxWidth(): ?float
    {
        return $this->maxWidth;
    }

    public function getMaxLength(): ?float
    {
        return $this->maxLength;
    }

    public function getVehicleCritair(): ?string
    {
        return $this->vehicleCritair;
    }

    public function getVehicleUsage(): ?string
    {
        return $this->vehicleUsage;
    }

    public function getVehicleType(): ?string
    {
        return $this->vehicleType;
    }

    public function update(
        float $maxWeight = null,
        float $maxHeight = null,
        float $maxWidth = null,
        float $maxLength = null,
    ): void {
        $this->maxWeight = $maxWeight;
        $this->maxHeight = $maxHeight;
        $this->maxWidth = $maxWidth;
        $this->maxLength = $maxLength;
    }
}
