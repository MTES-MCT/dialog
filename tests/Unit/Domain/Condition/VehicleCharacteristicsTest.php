<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition;

use App\Domain\Condition\Enum\VehicleCritairEnum;
use App\Domain\Condition\Enum\VehicleTypeEnum;
use App\Domain\Condition\Enum\VehicleUsageEnum;
use App\Domain\Condition\VehicleCharacteristics;
use App\Domain\Condition\Condition;
use PHPUnit\Framework\TestCase;

final class VehicleCharacteristicsTest extends TestCase
{
    public function testGetters(): void
    {
        $condition = $this->createMock(Condition::class);
        $vehicleCharacteristics = new VehicleCharacteristics(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $condition,
            VehicleUsageEnum::NON_COMMERCIAL,
            VehicleTypeEnum::ELECTRIC_VEHICLES,
            VehicleCritairEnum::EL,
            3.5,
            1.8,
            2.0,
            6.0,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $vehicleCharacteristics->getUuid());
        $this->assertSame($condition, $vehicleCharacteristics->getCondition());
        $this->assertSame(VehicleUsageEnum::NON_COMMERCIAL, $vehicleCharacteristics->getVehicleUsage());
        $this->assertSame(VehicleTypeEnum::ELECTRIC_VEHICLES, $vehicleCharacteristics->getVehicleType());
        $this->assertSame(VehicleCritairEnum::EL, $vehicleCharacteristics->getVehicleCritair());
        $this->assertSame(3.5, $vehicleCharacteristics->getMaxWeight());
        $this->assertSame(1.8, $vehicleCharacteristics->getMaxHeight());
        $this->assertSame(2.0, $vehicleCharacteristics->getMaxWidth());
        $this->assertSame(6.0, $vehicleCharacteristics->getMaxLength());
    }

    public function testUpdate(): void
    {
        $condition = $this->createMock(Condition::class);
        $vehicleCharacteristics = new VehicleCharacteristics(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $condition,
            VehicleUsageEnum::NON_COMMERCIAL,
            VehicleTypeEnum::ELECTRIC_VEHICLES,
            VehicleCritairEnum::EL,
            3.5,
            1.8,
            2.0,
            6.0,
        );

        $vehicleCharacteristics->update(
            1.1,
            1.2,
            1.3,
            1.4,
        );

        $this->assertSame(1.1, $vehicleCharacteristics->getMaxWeight());
        $this->assertSame(1.2, $vehicleCharacteristics->getMaxHeight());
        $this->assertSame(1.3, $vehicleCharacteristics->getMaxWidth());
        $this->assertSame(1.4, $vehicleCharacteristics->getMaxLength());
    }
}
