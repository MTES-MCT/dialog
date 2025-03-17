<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
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
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Specification\CanOrganizationInterveneOnGeometry;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class SaveLocationCommandHandlerTest extends TestCase
{
    private $commandBus;
    private $queryBus;
    private $locationRepository;
    private $idFactory;
    private $canOrganizationInterveneOnGeometry;

    public function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->canOrganizationInterveneOnGeometry = $this->createMock(CanOrganizationInterveneOnGeometry::class);
    }

    private function provideTestCreateNumberedRoad(): array
    {
        return [
            ['roadType' => RoadTypeEnum::DEPARTMENTAL_ROAD->value],
            ['roadType' => RoadTypeEnum::NATIONAL_ROAD->value],
        ];
    }

    /**
     * @dataProvider provideTestCreateNumberedRoad
     */
    public function testCreateNumberedRoad(string $roadType): void
    {
        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);
        $measure
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de');

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
                roadType: $roadType,
                geometry: 'geometry',
            ))
            ->willReturn($createdLocation);

        $this->canOrganizationInterveneOnGeometry
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de', 'geometry')
            ->willReturn(true);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($numberedRoadCommand));

        $handler = new SaveLocationCommandHandler(
            $this->commandBus,
            $this->queryBus,
            $this->locationRepository,
            $this->idFactory,
            $this->canOrganizationInterveneOnGeometry,
        );
        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = $roadType;
        $command->assignNumberedRoad($numberedRoadCommand);

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateNamedStreet(): void
    {
        $createdLocation = $this->createMock(Location::class);
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);

        $measure
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de');

        $namedStreetCommand = new SaveNamedStreetCommand();

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $this->canOrganizationInterveneOnGeometry
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de', 'geometry')
            ->willReturn(true);

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

        $handler = new SaveLocationCommandHandler(
            $this->commandBus,
            $this->queryBus,
            $this->locationRepository,
            $this->idFactory,
            $this->canOrganizationInterveneOnGeometry,
        );
        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->namedStreet = $namedStreetCommand;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateLocationWithGeometryThatOrganizationCannotInterveneOn(): void
    {
        $this->expectException(OrganizationCannotInterveneOnGeometryException::class);

        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de');

        $namedStreetCommand = new SaveNamedStreetCommand();

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->canOrganizationInterveneOnGeometry
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de', 'geometry')
            ->willReturn(false);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetNamedStreetGeometryQuery($namedStreetCommand))
            ->willReturn('geometry');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $handler = new SaveLocationCommandHandler(
            $this->commandBus,
            $this->queryBus,
            $this->locationRepository,
            $this->idFactory,
            $this->canOrganizationInterveneOnGeometry,
        );
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
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);
        $measure
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de');

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

        $this->canOrganizationInterveneOnGeometry
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de', '<geometry>')
            ->willReturn(true);

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

        $handler = new SaveLocationCommandHandler(
            $this->commandBus,
            $this->queryBus,
            $this->locationRepository,
            $this->idFactory,
            $this->canOrganizationInterveneOnGeometry,
        );
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
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de');

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::exactly(3))
            ->method('getNamedStreet')
            ->willReturn($namedStreet);
        $location
            ->expects(self::once())
            ->method('getMeasure')
            ->willReturn($measure);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(RoadTypeEnum::DEPARTMENTAL_ROAD->value, 'geometry');

        $this->canOrganizationInterveneOnGeometry
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de', 'geometry')
            ->willReturn(true);

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

        $handler = new SaveLocationCommandHandler(
            $this->commandBus,
            $this->queryBus,
            $this->locationRepository,
            $this->idFactory,
            $this->canOrganizationInterveneOnGeometry,
        );

        $command = new SaveLocationCommand($location);
        $command->namedStreet = null;
        $command->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $command->assignNumberedRoad($numberedRoadCommand);

        $result = $handler($command);

        $this->assertSame($location, $result);
    }

    public function testUpdateNamedStreetWithNumberedRoadDelation(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de');

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::exactly(3))
            ->method('getNumberedRoad')
            ->willReturn($numberedRoad);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(RoadTypeEnum::LANE->value, 'geometry');
        $location
            ->expects(self::once())
            ->method('getMeasure')
            ->willReturn($measure);

        $this->canOrganizationInterveneOnGeometry
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de', 'geometry')
            ->willReturn(true);

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

        $handler = new SaveLocationCommandHandler(
            $this->commandBus,
            $this->queryBus,
            $this->locationRepository,
            $this->idFactory,
            $this->canOrganizationInterveneOnGeometry,
        );

        $command = new SaveLocationCommand($location);
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->namedStreet = $namedStreetCommand;

        $result = $handler($command);

        $this->assertSame($location, $result);
    }

    public function testUpdateNamedStreetWithRawGeoJSONDelation(): void
    {
        $rawGeoJSON = $this->createMock(RawGeoJSON::class);
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de');

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::exactly(3))
            ->method('getRawGeoJSON')
            ->willReturn($rawGeoJSON);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(RoadTypeEnum::LANE->value, 'geometry');
        $location
            ->expects(self::once())
            ->method('getMeasure')
            ->willReturn($measure);

        $namedStreetCommand = new SaveNamedStreetCommand();
        $namedStreetCommand->roadType = RoadTypeEnum::LANE->value;
        $namedStreetCommand->location = $location;

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetNamedStreetGeometryQuery($namedStreetCommand, $location))
            ->willReturn('geometry');

        $this->canOrganizationInterveneOnGeometry
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de', 'geometry')
            ->willReturn(true);

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

        $handler = new SaveLocationCommandHandler(
            $this->commandBus,
            $this->queryBus,
            $this->locationRepository,
            $this->idFactory,
            $this->canOrganizationInterveneOnGeometry,
        );

        $command = new SaveLocationCommand($location);
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->namedStreet = $namedStreetCommand;

        $result = $handler($command);

        $this->assertSame($location, $result);
    }

    public function testUpdateCannotOrganizationInterveneOnGeometry(): void
    {
        $this->expectException(OrganizationCannotInterveneOnGeometryException::class);
        $rawGeoJSON = $this->createMock(RawGeoJSON::class);
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de');

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::exactly(2))
            ->method('getRawGeoJSON')
            ->willReturn($rawGeoJSON);
        $location
            ->expects(self::never())
            ->method('update');
        $location
            ->expects(self::once())
            ->method('getMeasure')
            ->willReturn($measure);

        $namedStreetCommand = new SaveNamedStreetCommand();
        $namedStreetCommand->roadType = RoadTypeEnum::LANE->value;
        $namedStreetCommand->location = $location;

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetNamedStreetGeometryQuery($namedStreetCommand, $location))
            ->willReturn('geometry');

        $this->canOrganizationInterveneOnGeometry
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('71d3dd7c-c6e9-4058-8948-0b4d8c6f15de', 'geometry')
            ->willReturn(false);

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $handler = new SaveLocationCommandHandler(
            $this->commandBus,
            $this->queryBus,
            $this->locationRepository,
            $this->idFactory,
            $this->canOrganizationInterveneOnGeometry,
        );

        $command = new SaveLocationCommand($location);
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->namedStreet = $namedStreetCommand;

        $result = $handler($command);

        $this->assertSame($location, $result);
    }
}
