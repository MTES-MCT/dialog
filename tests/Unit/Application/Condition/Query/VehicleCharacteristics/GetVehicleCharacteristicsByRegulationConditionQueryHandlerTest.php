<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Condition\Query\VehicleCharacteristics;

use App\Application\Condition\Query\VehicleCharacteristics\GetVehicleCharacteristicsByRegulationConditionQuery;
use App\Application\Condition\Query\VehicleCharacteristics\GetVehicleCharacteristicsByRegulationConditionQueryHandler;
use App\Domain\Condition\Repository\VehicleCharacteristicsRepositoryInterface;
use App\Domain\Condition\VehicleCharacteristics;
use PHPUnit\Framework\TestCase;

final class GetVehicleCharacteristicsByRegulationConditionQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $vehicleCharacteristics = $this->createMock(VehicleCharacteristics::class);
        $vehicleCharacteristicsRepository = $this->createMock(VehicleCharacteristicsRepositoryInterface::class);
        $vehicleCharacteristicsRepository
            ->expects(self::once())
            ->method('findOneByRegulationConditionUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($vehicleCharacteristics);

        $handler = new GetVehicleCharacteristicsByRegulationConditionQueryHandler($vehicleCharacteristicsRepository);
        $result = $handler(new GetVehicleCharacteristicsByRegulationConditionQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($vehicleCharacteristics, $result);
    }
}
