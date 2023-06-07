<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Condition\DeletePeriodCommand;
use App\Application\Regulation\Command\Condition\SavePeriodCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveMeasureCommandHandler;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveMeasureCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $measureRepository;
    private $commandBus;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->measureRepository = $this->createMock(MeasureRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('d035fec0-30f3-4134-95b9-d74c68eb53e3');

        $createdPeriod = $this->createMock(Period::class);
        $createdMeasure = $this->createMock(Measure::class);
        $location = $this->createMock(Location::class);

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
                        location: $location,
                        type: MeasureTypeEnum::ALTERNATE_ROAD->value,
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
        );

        $command = new SaveMeasureCommand();
        $command->location = $location;
        $command->type = MeasureTypeEnum::ALTERNATE_ROAD->value;
        $command->periods = [$periodCommand];

        $result = $handler($command);

        $this->assertSame($createdMeasure, $result);
    }

    public function testUpdate(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

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

        $measure = $this->createMock(Measure::class);

        $measure
            ->expects(self::once())
            ->method('update')
            ->with(MeasureTypeEnum::ALTERNATE_ROAD->value);

        $measure
            ->expects(self::exactly(2))
            ->method('getPeriods')
            ->willReturn([$period1, $period2]);

        $measure
            ->expects(self::once())
            ->method('removePeriod')
            ->with($period2);

        $this->measureRepository
            ->expects(self::never())
            ->method('add');

        $periodCommand = new SavePeriodCommand();
        $periodCommand1 = new SavePeriodCommand($period1);
        $periodCommand1->measure = $measure;

        $periodCommand2 = new SavePeriodCommand($period2);
        $periodCommand2->measure = $measure;

        $this->commandBus
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$this->equalTo($periodCommand1)], [$this->equalTo(new DeletePeriodCommand($period2))]);

        $handler = new SaveMeasureCommandHandler(
            $this->idFactory,
            $this->measureRepository,
            $this->commandBus,
        );

        $command = new SaveMeasureCommand($measure);
        $command->type = MeasureTypeEnum::ALTERNATE_ROAD->value;
        $command->periods = [$periodCommand1]; // Removes period2.

        $result = $handler($command);

        $this->assertSame($measure, $result);
    }
}
