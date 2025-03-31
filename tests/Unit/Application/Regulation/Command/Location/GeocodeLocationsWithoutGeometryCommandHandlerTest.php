<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\GeocodeLocationsWithoutGeometryCommand;
use App\Application\Regulation\Command\Location\GeocodeLocationsWithoutGeometryCommandHandler;
use App\Application\Regulation\Command\Location\GeocodeLocationsWithoutGeometryCommandResult;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class GeocodeLocationsWithoutGeometryCommandHandlerTest extends TestCase
{
    private $queryBus;
    private $locationRepository;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
    }

    public function testNoLocations(): void
    {
        $expectedResult = new GeocodeLocationsWithoutGeometryCommandResult(
            numLocations: 0,
            updatedLocationUuids: [],
            exceptions: [],
        );

        $this->locationRepository
            ->expects(self::once())
            ->method('findAllWithoutGeometry')
            ->willReturn([]);

        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $handler = new GeocodeLocationsWithoutGeometryCommandHandler($this->queryBus, $this->locationRepository);

        $this->assertEquals($expectedResult, $handler(new GeocodeLocationsWithoutGeometryCommand()));
    }

    public function testUpdate(): void
    {
        $organization = $this->createMock(Organization::class);
        $measure = $this->createMock(Measure::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $measure->expects(self::any())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder->expects(self::any())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);

        $location1 = $this->createMock(Location::class);
        $location1->expects(self::once())
            ->method('getMeasure')
            ->willReturn($measure);
        $location2 = $this->createMock(Location::class);
        $location2->expects(self::once())
            ->method('getMeasure')
            ->willReturn($measure);
        $location3 = $this->createMock(Location::class);
        $location3->expects(self::once())
            ->method('getMeasure')
            ->willReturn($measure);

        $namedStreet1 = $this->createMock(NamedStreet::class);
        $namedStreet2 = $this->createMock(NamedStreet::class);
        $namedStreet3 = $this->createMock(NamedStreet::class);

        $uuid1 = 'fb704ea8-6782-45bb-b8ac-75f7444d4760';
        $uuid2 = '689d9fd4-1cf9-4a1a-b50f-fc80916d8df8';
        $uuid3 = '80018f94-5929-4ca0-8ad9-ca0c97a6c46a';

        $this->locationRepository
            ->expects(self::once())
            ->method('findAllWithoutGeometry')
            ->willReturn([$location1, $location2, $location3]);

        $location1
            ->expects(self::exactly(2))
            ->method('getRoadType')
            ->willReturn(RoadTypeEnum::LANE->value);
        $location1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($uuid1);
        $location1
            ->expects(self::exactly(2))
            ->method('getNamedStreet')
            ->willReturn($namedStreet1);

        $location2
            ->expects(self::exactly(2))
            ->method('getRoadType')
            ->willReturn(RoadTypeEnum::LANE->value);
        $location2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($uuid2);
        $location2
            ->expects(self::exactly(2))
            ->method('getNamedStreet')
            ->willReturn($namedStreet2);

        $location3
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn(RoadTypeEnum::LANE->value);
        $location3
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($uuid3);
        $location3
            ->expects(self::exactly(2))
            ->method('getNamedStreet')
            ->willReturn($namedStreet3);

        $matcher = self::exactly(3);
        $this->queryBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($query) => match ($matcher->getInvocationCount()) {
                    1 => 'geometry1',
                    2 => 'geometry2',
                    3 => throw new GeocodingFailureException('oops 3'),
                },
            );

        $expectedResult = new GeocodeLocationsWithoutGeometryCommandResult(
            numLocations: 3,
            updatedLocationUuids: [$uuid1, $uuid2],
            exceptions: [$uuid3 => new GeocodingFailureException('oops 3')],
        );

        $handler = new GeocodeLocationsWithoutGeometryCommandHandler($this->queryBus, $this->locationRepository);

        $this->assertEquals($expectedResult, $handler(new GeocodeLocationsWithoutGeometryCommand()));
    }
}
