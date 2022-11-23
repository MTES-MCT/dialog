<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition;

use App\Domain\TrafficRegulation\Condition\Enum\VehicleCritairEnum;
use App\Domain\TrafficRegulation\Condition\Enum\VehicleTypeEnum;
use App\Domain\TrafficRegulation\Condition\Enum\VehicleUsageEnum;

class VehicleCharacteristics
{
    public function __construct(
        private string $uuid,
        private ?VehicleUsageEnum $vehicleUsage,
        private ?VehicleTypeEnum $vehicleType,
        private ?VehicleCritairEnum $vehicleCritair,
        private ?float $maxWeight,
        private ?float $maxHeight,
        private ?float $maxWidth,
        private ?float $maxLength,
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

    public function getVehicleCritair(): ?VehicleCritairEnum
    {
        return $this->vehicleCritair;
    }

    public function getVehicleUsage(): ?VehicleUsageEnum
    {
        return $this->vehicleUsage;
    }

    public function getVehicleType(): ?VehicleTypeEnum
    {
        return $this->vehicleType;
    }
}
