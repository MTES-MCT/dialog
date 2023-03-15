<?php

declare(strict_types=1);

namespace App\Domain\Condition;

use App\Domain\Condition\Period\Period;

class Condition
{
    private ?VehicleCharacteristics $vehicleCharacteristics = null;
    private ?Period $period = null;

    public function __construct(
        private string $uuid,
        private bool $negate,
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

    public function getVehicleCharacteristics(): ?VehicleCharacteristics
    {
        return $this->vehicleCharacteristics;
    }

    public function getPeriod(): ?Period
    {
        return $this->period;
    }
}
