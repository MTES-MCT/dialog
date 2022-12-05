<?php

declare(strict_types=1);

namespace App\Domain\Condition;

use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\RegulationOrder\RegulationOrder;

class RegulationCondition
{
    private ?RegulationOrder $regulationOrder = null;
    private ?VehicleCharacteristics $vehicleCharacteristics = null;
    private ?ConditionSet $conditionSet = null;
    private ?OverallPeriod $overallPeriod = null;

    public function __construct(
        private string $uuid,
        private bool $negate,
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

    public function getRegulationOrder(): ?RegulationOrder
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

    public function getOverallPeriod(): ?OverallPeriod
    {
        return $this->overallPeriod;
    }

    public function __toString(): string
    {
        return sprintf('RegulationCondition(uuid=\'%s\', ...)', $this->uuid);
    }
}
