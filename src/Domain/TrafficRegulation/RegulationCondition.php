<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation;

use App\Domain\TrafficRegulation\Condition\VehicleCharacteristics;

class RegulationCondition
{
    private ?VehicleCharacteristics $vehicleCharacteristics = null;

    public function __construct(
        private string $uuid,
        private bool $negate,
        private TrafficRegulation $trafficRegulation,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function isNegate(): bool
    {
        return $this->negate;
    }

    public function getTrafficRegulation(): TrafficRegulation
    {
        return $this->trafficRegulation;
    }

    public function getVehicleCharacteristics(): ?VehicleCharacteristics
    {
        return $this->vehicleCharacteristics;
    }

    public function setVehicleCharacteristics(VehicleCharacteristics $vehicleCharacteristics): void
    {
        $this->vehicleCharacteristics = $vehicleCharacteristics;
    }
}
