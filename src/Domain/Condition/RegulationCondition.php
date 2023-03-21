<?php

declare(strict_types=1);

namespace App\Domain\Condition;

use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;

class RegulationCondition
{
    private ?VehicleCharacteristics $vehicleCharacteristics = null;
    private ?Period $period = null;

    public function __construct(
        private string $uuid,
        private bool $negate,
        private Measure $measure,
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

    public function getMeasure(): Measure
    {
        return $this->measure;
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
