<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveMeasureCommandHandler;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveMeasureCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $measureRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->measureRepository = $this->createMock(MeasureRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('d035fec0-30f3-4134-95b9-d74c68eb53e3');

        $createdMeasure = $this->createMock(Measure::class);
        $location = $this->createMock(Location::class);

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

        $handler = new SaveMeasureCommandHandler(
            $this->idFactory,
            $this->measureRepository,
        );

        $command = new SaveMeasureCommand();
        $command->location = $location;
        $command->type = MeasureTypeEnum::ALTERNATE_ROAD->value;

        $result = $handler($command);

        $this->assertSame($createdMeasure, $result);
    }

    public function testUpdate(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('update')
            ->with(MeasureTypeEnum::ALTERNATE_ROAD->value);

        $this->measureRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveMeasureCommandHandler(
            $this->idFactory,
            $this->measureRepository,
        );

        $command = new SaveMeasureCommand($measure);
        $command->type = MeasureTypeEnum::ALTERNATE_ROAD->value;

        $result = $handler($command);

        $this->assertSame($measure, $result);
    }
}
