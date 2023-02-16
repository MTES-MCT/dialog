<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition;

use App\Domain\Condition\ConditionSet;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Regulation\RegulationOrder;
use PHPUnit\Framework\TestCase;

final class RegulationConditionTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $parentConditionSet = $this->createMock(ConditionSet::class);

        $regulationCondition = new RegulationCondition(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            false,
            $regulationOrder,
            $parentConditionSet,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $regulationCondition->getUuid());
        $this->assertSame(false, $regulationCondition->isNegate());
        $this->assertSame($regulationOrder, $regulationCondition->getRegulationOrder());
        $this->assertSame($parentConditionSet, $regulationCondition->getParentConditionSet());
        $this->assertSame(null, $regulationCondition->getVehicleCharacteristics()); // Automatically set by Doctrine
        $this->assertSame(null, $regulationCondition->getLocation()); // Automatically set by Doctrine
        $this->assertSame(null, $regulationCondition->getConditionSet()); // Automatically set by Doctrine
        $this->assertSame(null, $regulationCondition->getOverallPeriod()); // Automatically set by Doctrine
    }
}
