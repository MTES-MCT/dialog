<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\DeleteLocationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Period\DeletePeriodCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveMeasureCommandHandler;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveMeasureCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $measureRepository;
    private $commandBus;
    private $dateUtils;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->measureRepository = $this->createMock(MeasureRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('d035fec0-30f3-4134-95b9-d74c68eb53e3');

        $now = new \DateTimeImmutable('2023-06-13');
        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $createdPeriod = $this->createMock(Period::class);
        $createdLocation = $this->createMock(Location::class);
        $createdMeasure = $this->createMock(Measure::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);

        $createdMeasure
            ->expects(self::once())
            ->method('addPeriod')
            ->with($createdPeriod);

        $createdMeasure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);

        $this->measureRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Measure(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        regulationOrder: $regulationOrder,
                        type: MeasureTypeEnum::ALTERNATE_ROAD->value,
                        createdAt: $now,
                    ),
                ),
            )
            ->willReturn($createdMeasure);

        $periodCommand = new SavePeriodCommand();
        $locationCommand = new SaveLocationCommand();

        $handleMatcher = self::exactly(2);
        $this->commandBus
            ->expects($handleMatcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($handleMatcher->getInvocationCount()) {
                    1 => $this->assertEquals($periodCommand, $command) ?: $createdPeriod,
                    2 => $this->assertEquals($locationCommand, $command) ?: $createdLocation,
                },
            );

        $handler = new SaveMeasureCommandHandler(
            $this->idFactory,
            $this->measureRepository,
            $this->commandBus,
            $this->dateUtils,
        );

        $command = new SaveMeasureCommand($regulationOrder);
        $command->type = MeasureTypeEnum::ALTERNATE_ROAD->value;
        $command->periods = [$periodCommand];
        $command->locations = [$locationCommand];

        $result = $handler($command);

        $this->assertSame($createdMeasure, $result);
    }

    public function testCreateWithCreatedCommand(): void
    {
        $createdAt = new \DateTimeImmutable('2023-06-12');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('d035fec0-30f3-4134-95b9-d74c68eb53e3');
        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $createdPeriod = $this->createMock(Period::class);
        $createdMeasure = $this->createMock(Measure::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);

        $createdMeasure
            ->expects(self::once())
            ->method('addPeriod')
            ->with($createdPeriod);

        $this->measureRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Measure(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        regulationOrder: $regulationOrder,
                        type: MeasureTypeEnum::ALTERNATE_ROAD->value,
                        createdAt: $createdAt,
                    ),
                ),
            )
            ->willReturn($createdMeasure);

        $periodCommand = new SavePeriodCommand();
        $periodCommand->measure = $createdMeasure;

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($periodCommand))
            ->willReturn($createdPeriod);

        $handler = new SaveMeasureCommandHandler(
            $this->idFactory,
            $this->measureRepository,
            $this->commandBus,
            $this->dateUtils,
        );

        $command = new SaveMeasureCommand($regulationOrder);
        $command->type = MeasureTypeEnum::ALTERNATE_ROAD->value;
        $command->createdAt = $createdAt;
        $command->periods = [$periodCommand];

        $result = $handler($command);

        $this->assertSame($createdMeasure, $result);
    }

    public function testCreateWithVehicleSet(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('d035fec0-30f3-4134-95b9-d74c68eb53e3');

        $now = new \DateTimeImmutable('2023-06-13');
        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $createdVehicleSet = $this->createMock(VehicleSet::class);
        $createdMeasure = $this->createMock(Measure::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);

        $createdMeasure
            ->expects(self::once())
            ->method('setVehicleSet')
            ->with($createdVehicleSet);

        $this->measureRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Measure(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        regulationOrder: $regulationOrder,
                        type: MeasureTypeEnum::ALTERNATE_ROAD->value,
                        createdAt: $now,
                    ),
                ),
            )
            ->willReturn($createdMeasure);

        $vehicleSetCommand = new SaveVehicleSetCommand();
        $vehicleSetCommand->measure = $createdMeasure;

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($vehicleSetCommand))
            ->willReturn($createdVehicleSet);

        $handler = new SaveMeasureCommandHandler(
            $this->idFactory,
            $this->measureRepository,
            $this->commandBus,
            $this->dateUtils,
        );

        $command = new SaveMeasureCommand($regulationOrder);
        $command->type = MeasureTypeEnum::ALTERNATE_ROAD->value;
        $command->vehicleSet = $vehicleSetCommand;

        $result = $handler($command);

        $this->assertSame($createdMeasure, $result);
    }

    public function testUpdate(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $period1 = $this->createMock(Period::class);
        $period1
            ->expects(self::exactly(2))
            ->method('getUuid')
            ->willReturn('e7ceb504-e6d2-43b7-9e1f-43f4baa17907');

        $period2 = $this->createMock(Period::class);
        $period2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('28accfd6-d896-4ed9-96a3-1754f288f511');

        $location1 = $this->createMock(Location::class);
        $location1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('065af978-a0f0-7cf8-8000-ce7f83c57ca3');

        $location2 = $this->createMock(Location::class);
        $location2
            ->expects(self::exactly(3))
            ->method('getUuid')
            ->willReturn('065af992-ecb9-7afd-8000-a1ac6e020f2f');

        $measure = $this->createMock(Measure::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);

        $measure
            ->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn(new \DateTimeImmutable('2023-06-01'));

        $measure
            ->expects(self::once())
            ->method('update')
            ->with(MeasureTypeEnum::ALTERNATE_ROAD->value);

        $measure
            ->expects(self::exactly(2))
            ->method('getPeriods')
            ->willReturn([$period1, $period2]);

        $measure
            ->expects(self::exactly(2))
            ->method('getLocations')
            ->willReturn([$location1, $location2]);

        $measure
            ->expects(self::once())
            ->method('setVehicleSet')
            ->with(null);

        $measure
            ->expects(self::once())
            ->method('removePeriod')
            ->with($period2);

        $measure
            ->expects(self::once())
            ->method('removeLocation')
            ->with($location1);

        $this->measureRepository
            ->expects(self::never())
            ->method('add');

        $periodCommand1 = new SavePeriodCommand($period1);
        $locationCommand2 = new SaveLocationCommand($location2);

        $this->commandBus
            ->expects(self::exactly(4))
            ->method('handle')
            ->withConsecutive(
                [$this->equalTo($periodCommand1)],
                [$this->equalTo(new DeletePeriodCommand($period2))],
                [$this->equalTo($locationCommand2)],
                [$this->equalTo(new DeleteLocationCommand($location1))],
            )
            ->willReturnOnConsecutiveCalls(null, null, $location2, null);

        $handler = new SaveMeasureCommandHandler(
            $this->idFactory,
            $this->measureRepository,
            $this->commandBus,
            $this->dateUtils,
        );

        $command = SaveMeasureCommand::create($regulationOrder, $measure);
        $command->type = MeasureTypeEnum::ALTERNATE_ROAD->value;
        $command->vehicleSet = null; // Removes vehicle set
        $command->periods = [$periodCommand1]; // Removes period2.
        $command->locations = [$locationCommand2]; // Removes location1.

        $result = $handler($command);

        $this->assertSame($measure, $result);
    }
}
