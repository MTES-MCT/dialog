<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use PHPUnit\Framework\TestCase;

final class SaveMeasureCommandTest extends TestCase
{
    public function testCreateWithoutMeasure(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $command = SaveMeasureCommand::create($regulationOrder);

        $this->assertEmpty($command->measure);
        $this->assertEmpty($command->type);
        $this->assertSame($regulationOrder, $command->regulationOrder);
        $this->assertEquals([new SaveLocationCommand()], $command->locations);
        $this->assertEmpty($command->periods);
    }

    public function testCreateWithMeasure(): void
    {
        $location1 = $this->createMock(Location::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $vehicleSet = $this->createMock(VehicleSet::class);
        $period = $this->createMock(Period::class);
        $measure = $this->createMock(Measure::class);
        $createdAt = new \DateTimeImmutable('2023-06-01');

        $measure
            ->expects(self::once())
            ->method('getLocations')
            ->willReturn([$location1]);
        $measure
            ->expects(self::once())
            ->method('getVehicleSet')
            ->willReturn($vehicleSet);

        $measure
            ->expects(self::once())
            ->method('getPeriods')
            ->willReturn([$period]);

        $measure
            ->expects(self::once())
            ->method('getType')
            ->willReturn(MeasureTypeEnum::ALTERNATE_ROAD->value);

        $measure
            ->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn($createdAt);

        $command = SaveMeasureCommand::create($regulationOrder, $measure);

        $this->assertSame($measure, $command->measure);
        $this->assertSame(MeasureTypeEnum::ALTERNATE_ROAD->value, $command->type);
        $this->assertSame($regulationOrder, $command->regulationOrder);
        $this->assertEquals(new SaveVehicleSetCommand($vehicleSet), $command->vehicleSet);
        $this->assertEquals([new SavePeriodCommand($period)], $command->periods);
        $this->assertEquals([new SaveLocationCommand($location1)], $command->locations);
        $this->assertEquals($createdAt, $command->createdAt);
    }
}
