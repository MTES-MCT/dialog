<?php

declare(strict_types=1);

namespace App\Tests\Domain\TrafficRegulation\Condition;

use App\Domain\TrafficRegulation\Condition\Enum\VehicleCritairEnum;
use App\Domain\TrafficRegulation\Condition\Enum\VehicleTypeEnum;
use App\Domain\TrafficRegulation\Condition\Enum\VehicleUsageEnum;
use App\Domain\TrafficRegulation\Condition\VehicleCharacteristics;
use App\Domain\TrafficRegulation\RegulationCondition;
use PHPUnit\Framework\TestCase;

final class VehicleCharacteristicsTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);
        $vehicleCharacteristics = new VehicleCharacteristics(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $regulationCondition,
            VehicleUsageEnum::NON_COMMERCIAL,
            VehicleTypeEnum::ELECTRIC_VEHICLES,
            VehicleCritairEnum::EL,
            3.5,
            1.8,
            2.0,
            6.0,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $vehicleCharacteristics->getUuid());
        $this->assertSame($regulationCondition, $vehicleCharacteristics->getRegulationCondition());
        $this->assertSame(VehicleUsageEnum::NON_COMMERCIAL, $vehicleCharacteristics->getVehicleUsage());
        $this->assertSame(VehicleTypeEnum::ELECTRIC_VEHICLES, $vehicleCharacteristics->getVehicleType());
        $this->assertSame(VehicleCritairEnum::EL, $vehicleCharacteristics->getVehicleCritair());
        $this->assertSame(3.5, $vehicleCharacteristics->getMaxWeight());
        $this->assertSame(1.8, $vehicleCharacteristics->getMaxHeight());
        $this->assertSame(2.0, $vehicleCharacteristics->getMaxWidth());
        $this->assertSame(6.0, $vehicleCharacteristics->getMaxLength());
    }
}
