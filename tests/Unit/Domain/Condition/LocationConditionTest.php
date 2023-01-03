<?php

declare(strict_types=1);

namespace App\Tests\Domain\Condition;

use App\Domain\Condition\LocationCondition;
use App\Domain\Condition\RegulationCondition;
use PHPUnit\Framework\TestCase;

final class LocationConditionTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);
        $locationCondition = new LocationCondition(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $regulationCondition,
            "POLYGON ((0 0 0,4 0 0,4 4 0,0 4 0,0 0 0),(1 1 0,2 1 0,2 2 0,1 2 0,1 1 0))"
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $locationCondition->getUuid());
        $this->assertSame($regulationCondition, $locationCondition->getRegulationCondition());
        $this->assertSame("POLYGON ((0 0 0,4 0 0,4 4 0,0 4 0,0 0 0),(1 1 0,2 1 0,2 2 0,1 2 0,1 1 0))", $locationCondition->getGeometry());
    }
}
