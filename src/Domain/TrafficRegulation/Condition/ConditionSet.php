<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition;

use App\Domain\TrafficRegulation\Condition\Enum\OperatorEnum;
use App\Domain\TrafficRegulation\RegulationCondition;

class ConditionSet
{
    /** @var RegulationCondition[] */
    private array $conditions = [];

    public function __construct(
        private string $uuid,
        private OperatorEnum $operator,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getOperator(): OperatorEnum
    {
        return $this->operator;
    }

    public function addCondition(RegulationCondition $regulationCondition): void
    {
        if (\in_array($regulationCondition, $this->conditions, true)) {
            return;
        }

        $this->conditions[] = $regulationCondition;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }
}
