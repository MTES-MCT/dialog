<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommandHandler;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveLocationCommandHandlerTest extends TestCase
{
    private MockObject $commandBus;

    public function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testCreateNamedStreet(): void
    {
        $measure = $this->createMock(Measure::class);
        $createdLocation = $this->createMock(Location::class);

        $namedStreetCommand = new SaveNamedStreetCommand();
        $namedStreetCommand->measure = $measure;
        $namedStreetCommand->roadType = RoadTypeEnum::LANE->value;

        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($namedStreetCommand))
            ->willReturn($createdLocation);

        $handler = new SaveLocationCommandHandler($this->commandBus);

        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->namedStreet = $namedStreetCommand;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateNumberedRoad(): void
    {
        $measure = $this->createMock(Measure::class);
        $createdLocation = $this->createMock(Location::class);

        $numberedRoadCommand = new SaveNumberedRoadCommand();
        $numberedRoadCommand->measure = $measure;
        $numberedRoadCommand->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;

        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($numberedRoadCommand))
            ->willReturn($createdLocation);

        $handler = new SaveLocationCommandHandler($this->commandBus);

        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->numberedRoad = $numberedRoadCommand;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    // TODO : test update with and without delation
}
