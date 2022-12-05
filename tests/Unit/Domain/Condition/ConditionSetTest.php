<?php

declare(strict_types=1);

namespace App\Tests\Domain\Condition;

use App\Domain\Condition\ConditionSet;
use App\Domain\Condition\Enum\OperatorEnum;
use App\Domain\Condition\RegulationCondition;
use PHPUnit\Framework\TestCase;

final class ConditionSetTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);
        $conditionSet = new ConditionSet(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            OperatorEnum::AND,
            $regulationCondition,
        );
        $regulationCondition1 = $this->createMock(RegulationCondition::class);
        $regulationCondition2 = $this->createMock(RegulationCondition::class);
        $conditionSet->addCondition($regulationCondition1);
        $conditionSet->addCondition($regulationCondition1); // Test deduplication
        $conditionSet->addCondition($regulationCondition2);

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $conditionSet->getUuid());
        $this->assertSame(OperatorEnum::AND, $conditionSet->getOperator());
        $this->assertSame($regulationCondition, $conditionSet->getRegulationCondition());
        $this->assertSame([$regulationCondition1, $regulationCondition2], $conditionSet->getConditions());
    }
}
