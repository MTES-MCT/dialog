<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\DeleteNamedStreetCommand;
use App\Application\Regulation\Command\Location\DeleteNumberedRoadCommand;
use App\Application\Regulation\Command\Location\DeleteRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommandHandler;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Query\Location\GetNamedStreetGeometryQuery;
use App\Application\Regulation\Query\Location\GetNumberedRoadGeometryQuery;
use App\Application\Regulation\Query\Location\GetRawGeoJSONGeometryQuery;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveLocationCommandHandlerTest extends TestCase
{
    private MockObject $commandBus;
    private MockObject $queryBus;
    private MockObject $locationRepository;
    private MockObject $idFactory;

    public function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
    }

    public function testCreateNumberedRoad(): void
    {
        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);

        $numberedRoadCommand = new SaveNumberedRoadCommand();

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetNumberedRoadGeometryQuery($numberedRoadCommand))
            ->willReturn('geometry');

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with(new Location(
                uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                measure: $measure,
                roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                geometry: 'geometry',
            ))
            ->willReturn($createdLocation);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($numberedRoadCommand));

        $handler = new SaveLocationCommandHandler($this->commandBus, $this->queryBus, $this->locationRepository, $this->idFactory);
        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $command->numberedRoad = $numberedRoadCommand;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateNamedStreet(): void
    {
        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);

        $namedStreetCommand = new SaveNamedStreetCommand();

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetNamedStreetGeometryQuery($namedStreetCommand))
            ->willReturn('geometry');

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with(new Location(
                uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                measure: $measure,
                roadType: RoadTypeEnum::LANE->value,
                geometry: 'geometry',
            ))
            ->willReturn($createdLocation);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($namedStreetCommand));

        $handler = new SaveLocationCommandHandler($this->commandBus, $this->queryBus, $this->locationRepository, $this->idFactory);
        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->namedStreet = $namedStreetCommand;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateRawGeoJSON(): void
    {
        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);

        $rawGeoJSONCommand = new SaveRawGeoJSONCommand();
        $rawGeoJSONCommand->label = 'Evénement spécial';
        $rawGeoJSONCommand->geometry = '<geometry>';

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetRawGeoJSONGeometryQuery('<geometry>'))
            ->willReturn('<geometry>');

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with(new Location(
                uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                measure: $measure,
                roadType: RoadTypeEnum::RAW_GEOJSON->value,
                geometry: '<geometry>',
            ))
            ->willReturn($createdLocation);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($rawGeoJSONCommand));

        $handler = new SaveLocationCommandHandler($this->commandBus, $this->queryBus, $this->locationRepository, $this->idFactory);
        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $command->rawGeoJSON = $rawGeoJSONCommand;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testUpdateNumberedRoadWithNamedStreetDeletion(): void
    {
        $namedStreet = $this->createMock(NamedStreet::class);
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::exactly(3))
            ->method('getNamedStreet')
            ->willReturn($namedStreet);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(RoadTypeEnum::DEPARTMENTAL_ROAD->value, 'geometry');

        $numberedRoadCommand = new SaveNumberedRoadCommand();
        $numberedRoadCommand->location = $location;
        $numberedRoadCommand->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetNumberedRoadGeometryQuery($numberedRoadCommand, $location))
            ->willReturn('geometry');

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($numberedRoadCommand, $command) ?: $location,
                    2 => $this->assertEquals(new DeleteNamedStreetCommand($namedStreet), $command),
                },
            );

        $handler = new SaveLocationCommandHandler($this->commandBus, $this->queryBus, $this->locationRepository, $this->idFactory);

        $command = new SaveLocationCommand($location);
        $command->numberedRoad = $numberedRoadCommand;
        $command->namedStreet = null;
        $command->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;

        $result = $handler($command);

        $this->assertSame($location, $result);
    }

    public function testUpdateNamedStreetWithNumberedRoadDelation(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::exactly(3))
            ->method('getNumberedRoad')
            ->willReturn($numberedRoad);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(RoadTypeEnum::LANE->value, 'geometry');

        $namedStreetCommand = new SaveNamedStreetCommand();
        $namedStreetCommand->roadType = RoadTypeEnum::LANE->value;
        $namedStreetCommand->location = $location;

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetNamedStreetGeometryQuery($namedStreetCommand, $location))
            ->willReturn('geometry');

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($namedStreetCommand, $command) ?: $location,
                    2 => $this->assertEquals(new DeleteNumberedRoadCommand($numberedRoad), $command),
                },
            );

        $handler = new SaveLocationCommandHandler($this->commandBus, $this->queryBus, $this->locationRepository, $this->idFactory);

        $command = new SaveLocationCommand($location);
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->numberedRoad = null;
        $command->namedStreet = $namedStreetCommand;

        $result = $handler($command);

        $this->assertSame($location, $result);
    }

    public function testUpdateNamedStreetWithRawGeoJSONDelation(): void
    {
        $rawGeoJSON = $this->createMock(RawGeoJSON::class);
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::exactly(3))
            ->method('getRawGeoJSON')
            ->willReturn($rawGeoJSON);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(RoadTypeEnum::LANE->value, 'geometry');

        $namedStreetCommand = new SaveNamedStreetCommand();
        $namedStreetCommand->roadType = RoadTypeEnum::LANE->value;
        $namedStreetCommand->location = $location;

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetNamedStreetGeometryQuery($namedStreetCommand, $location))
            ->willReturn('geometry');

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($namedStreetCommand, $command) ?: $location,
                    2 => $this->assertEquals(new DeleteRawGeoJSONCommand($rawGeoJSON), $command),
                },
            );

        $handler = new SaveLocationCommandHandler($this->commandBus, $this->queryBus, $this->locationRepository, $this->idFactory);

        $command = new SaveLocationCommand($location);
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->numberedRoad = null;
        $command->namedStreet = $namedStreetCommand;

        $result = $handler($command);

        $this->assertSame($location, $result);
    }
}
