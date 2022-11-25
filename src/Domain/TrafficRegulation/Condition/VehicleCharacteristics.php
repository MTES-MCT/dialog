<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition;

use App\Domain\TrafficRegulation\Condition\Enum\VehicleCritairEnum;
use App\Domain\TrafficRegulation\Condition\Enum\VehicleTypeEnum;
use App\Domain\TrafficRegulation\Condition\Enum\VehicleUsageEnum;
use App\Domain\TrafficRegulation\RegulationCondition;

class VehicleCharacteristics
{
    public function __construct(
        private string $uuid,
        private RegulationCondition $regulationCondition,
        private ?VehicleUsageEnum $vehicleUsage = null,
        private ?VehicleTypeEnum $vehicleType = null,
        private ?VehicleCritairEnum $vehicleCritair = null,
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

    public function getRegulationCondition(): RegulationCondition
    {
        return $this->regulationCondition;
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
