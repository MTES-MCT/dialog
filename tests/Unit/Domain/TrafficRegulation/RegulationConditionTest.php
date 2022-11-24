<?php

declare(strict_types=1);

namespace App\Tests\Domain\TrafficRegulation;

use App\Domain\TrafficRegulation\Condition\ConditionSet;
use App\Domain\TrafficRegulation\Condition\Period\OverallPeriod;
use App\Domain\TrafficRegulation\Condition\VehicleCharacteristics;
use App\Domain\TrafficRegulation\RegulationCondition;
use App\Domain\TrafficRegulation\TrafficRegulation;
use PHPUnit\Framework\TestCase;

final class RegulationConditionTest extends TestCase
{
    public function testGetters(): void
    {
        $conditionSet = $this->createMock(ConditionSet::class);
        $overallPeriod = $this->createMock(OverallPeriod::class);
        $vehicleCharacterics = $this->createMock(VehicleCharacteristics::class);
        $trafficRegulation = new TrafficRegulation('6598fd41-85cb-42a6-9693-1bc45f4dd392');
        $regulationCondition = new RegulationCondition(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            false,
            $trafficRegulation,
            $conditionSet,
            $overallPeriod,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $regulationCondition->getUuid());
        $this->assertSame(false, $regulationCondition->isNegate());
        $this->assertSame($trafficRegulation, $regulationCondition->getTrafficRegulation());
        $this->assertSame(null, $regulationCondition->getVehicleCharacteristics());
        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationCondition->getTrafficRegulation()->getUuid());
        $regulationCondition->setVehicleCharacteristics($vehicleCharacterics);
        $this->assertSame($vehicleCharacterics, $regulationCondition->getVehicleCharacteristics());
        $this->assertSame($conditionSet, $regulationCondition->getParentConditionSet());
        $this->assertSame($overallPeriod, $regulationCondition->getOverallPeriod());
    }
}
