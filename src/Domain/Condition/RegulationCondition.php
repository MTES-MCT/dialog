<?php

declare(strict_types=1);

namespace App\Domain\Condition;

use App\Domain\Regulation\RegulationOrder;

class RegulationCondition
{
    private ?VehicleCharacteristics $vehicleCharacteristics = null;
    private ?ConditionSet $conditionSet = null;

    public function __construct(
        private string $uuid,
        private bool $negate,
        private RegulationOrder $regulationOrder,
        private ?ConditionSet $parentConditionSet = null,
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

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }

    public function getParentConditionSet(): ?ConditionSet
    {
        return $this->parentConditionSet;
    }

    public function getVehicleCharacteristics(): ?VehicleCharacteristics
    {
        return $this->vehicleCharacteristics;
    }

    public function getConditionSet(): ?ConditionSet
    {
        return $this->conditionSet;
    }
}
