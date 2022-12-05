<?php

declare(strict_types=1);

namespace App\Domain\Condition;

use App\Domain\Condition\Enum\OperatorEnum;

class ConditionSet
{
    /** @var RegulationCondition[] */
    private array $conditions = [];

    public function __construct(
        private string $uuid,
        private OperatorEnum $operator,
        private RegulationCondition $regulationCondition,
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

    public function getRegulationCondition(): RegulationCondition
    {
        return $this->regulationCondition;
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
