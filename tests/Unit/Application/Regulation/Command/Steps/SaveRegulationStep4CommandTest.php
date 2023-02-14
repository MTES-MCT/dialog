<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command\Steps\Factory;

use App\Application\Regulation\Command\Steps\SaveRegulationStep4Command;
use App\Domain\Condition\VehicleCharacteristics;
use App\Domain\Regulation\RegulationOrder;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep4CommandTest extends TestCase
{
    public function testWithoutVehicleCharacteristics(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $command = SaveRegulationStep4Command::create($regulationOrder);

        $this->assertEmpty($command->maxHeight);
        $this->assertEmpty($command->maxLength);
        $this->assertEmpty($command->maxWeight);
        $this->assertEmpty($command->maxWidth);
    }

    public function testWithVehicleCharacteristics(): void
    {
        $vehicleCharacteristics = $this->createMock(VehicleCharacteristics::class);
        $vehicleCharacteristics
            ->expects(self::once())
            ->method('getMaxHeight')
            ->willReturn(1.1);
        $vehicleCharacteristics
            ->expects(self::once())
            ->method('getMaxLength')
            ->willReturn(2.2);
        $vehicleCharacteristics
            ->expects(self::once())
            ->method('getMaxWeight')
            ->willReturn(3.3);
        $vehicleCharacteristics
            ->expects(self::once())
            ->method('getMaxWidth')
            ->willReturn(4.4);

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $command = SaveRegulationStep4Command::create($regulationOrder, $vehicleCharacteristics);

        $this->assertSame($command->maxHeight, 1.1);
        $this->assertSame($command->maxLength, 2.2);
        $this->assertSame($command->maxWeight, 3.3);
        $this->assertSame($command->maxWidth, 4.4);
    }
}
