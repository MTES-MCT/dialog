<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\DeleteNamedStreetCommand;
use App\Application\Regulation\Command\Location\DeleteNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommandHandler;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
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

    public function testUpdateNumberedRoadWithNamedStreetDelation(): void
    {
        $namedStreet = $this->createMock(NamedStreet::class);
        $measure = $this->createMock(Measure::class);
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::exactly(4))
            ->method('getNamedStreet')
            ->willReturn($namedStreet);

        $numberedRoadCommand = new SaveNumberedRoadCommand();
        $numberedRoadCommand->measure = $measure;
        $numberedRoadCommand->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals(new DeleteNamedStreetCommand($namedStreet), $command),
                    2 => $this->assertEquals($numberedRoadCommand, $command) ?: $location,
                },
            );

        $handler = new SaveLocationCommandHandler($this->commandBus);

        $command = new SaveLocationCommand($location);
        $command->measure = $measure;
        $command->numberedRoad = $numberedRoadCommand;
        $command->namedStreet = null;

        $result = $handler($command);

        $this->assertSame($location, $result);
    }

    public function testUpdateNamedStreetWithNumberedRoadDelation(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $measure = $this->createMock(Measure::class);
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::exactly(4))
            ->method('getNumberedRoad')
            ->willReturn($numberedRoad);

        $namedStreetCommand = new SaveNamedStreetCommand();
        $namedStreetCommand->measure = $measure;
        $namedStreetCommand->roadType = RoadTypeEnum::LANE->value;

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals(new DeleteNumberedRoadCommand($numberedRoad), $command),
                    2 => $this->assertEquals($namedStreetCommand, $command) ?: $location,
                },
            );

        $handler = new SaveLocationCommandHandler($this->commandBus);

        $command = new SaveLocationCommand($location);
        $command->measure = $measure;
        $command->numberedRoad = null;
        $command->namedStreet = $namedStreetCommand;

        $result = $handler($command);

        $this->assertSame($location, $result);
    }

    public function testLogicException(): void
    {
        $this->expectException(\LogicException::class);

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $handler = new SaveLocationCommandHandler($this->commandBus);
        $command = new SaveLocationCommand();
        $handler($command);
    }
}
