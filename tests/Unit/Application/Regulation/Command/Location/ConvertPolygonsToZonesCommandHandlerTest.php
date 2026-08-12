<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Exception\GeocodingFailureException;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\ConvertPolygonsToZonesCommand;
use App\Application\Regulation\Command\Location\ConvertPolygonsToZonesCommandHandler;
use App\Application\Regulation\Command\Location\ConvertPolygonsToZonesCommandResult;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Location\Zone;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;
use App\Domain\Regulation\Repository\NumberedRoadRepositoryInterface;
use App\Domain\Regulation\Repository\RawGeoJSONRepositoryInterface;
use App\Domain\Regulation\Repository\ZoneRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ConvertPolygonsToZonesCommandHandlerTest extends TestCase
{
    private $locationRepository;
    private $zoneRepository;
    private $rawGeoJSONRepository;
    private $namedStreetRepository;
    private $numberedRoadRepository;
    private $idFactory;
    private $roadGeocoder;

    public function setUp(): void
    {
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->zoneRepository = $this->createMock(ZoneRepositoryInterface::class);
        $this->rawGeoJSONRepository = $this->createMock(RawGeoJSONRepositoryInterface::class);
        $this->namedStreetRepository = $this->createMock(NamedStreetRepositoryInterface::class);
        $this->numberedRoadRepository = $this->createMock(NumberedRoadRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
    }

    private function createHandler(): ConvertPolygonsToZonesCommandHandler
    {
        return new ConvertPolygonsToZonesCommandHandler(
            $this->locationRepository,
            $this->zoneRepository,
            $this->rawGeoJSONRepository,
            $this->namedStreetRepository,
            $this->numberedRoadRepository,
            $this->idFactory,
            $this->roadGeocoder,
        );
    }

    public function testNoLocations(): void
    {
        $this->locationRepository
            ->expects(self::once())
            ->method('findAllWithPolygonGeometry')
            ->willReturn([]);

        $this->roadGeocoder
            ->expects(self::never())
            ->method('findSectionsInArea');

        $expectedResult = new ConvertPolygonsToZonesCommandResult(
            numLocations: 0,
            convertedLocationUuids: [],
            exceptions: [],
        );

        $this->assertEquals($expectedResult, ($this->createHandler())(new ConvertPolygonsToZonesCommand()));
    }

    public function testConvertRawGeoJSON(): void
    {
        $rawGeoJSON = $this->createMock(RawGeoJSON::class);
        $rawGeoJSON
            ->method('getLabel')
            ->willReturn('Zone piétonne');

        $location = $this->createMock(Location::class);
        $location
            ->method('getUuid')
            ->willReturn('0658c568-dfbb-4c7a-a1dc-ff5a3ce13f16');
        $location
            ->method('getGeometry')
            ->willReturn('<polygon>');
        $location
            ->method('getRawGeoJSON')
            ->willReturn($rawGeoJSON);

        $this->locationRepository
            ->expects(self::once())
            ->method('findAllWithPolygonGeometry')
            ->willReturn([$location]);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('findSectionsInArea')
            ->with('<polygon>', [RoadGeocoderInterface::HIGHWAY], true)
            ->willReturn('<sections>');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('f2c03654-4ad9-4eed-827d-dab4ebec5a29');

        $createdZone = $this->createMock(Zone::class);

        $this->zoneRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Zone(
                        uuid: 'f2c03654-4ad9-4eed-827d-dab4ebec5a29',
                        location: $location,
                        label: 'Zone piétonne',
                        geometry: '<polygon>',
                    ),
                ),
            )
            ->willReturn($createdZone);

        $location
            ->expects(self::once())
            ->method('setZone')
            ->with($createdZone);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(RoadTypeEnum::ZONE->value, '<sections>');

        $this->rawGeoJSONRepository
            ->expects(self::once())
            ->method('delete')
            ->with($rawGeoJSON);

        $this->namedStreetRepository
            ->expects(self::never())
            ->method('delete');

        $expectedResult = new ConvertPolygonsToZonesCommandResult(
            numLocations: 1,
            convertedLocationUuids: ['0658c568-dfbb-4c7a-a1dc-ff5a3ce13f16'],
            exceptions: [],
        );

        $this->assertEquals($expectedResult, ($this->createHandler())(new ConvertPolygonsToZonesCommand()));
    }

    public function testConvertNamedStreet(): void
    {
        $namedStreet = $this->createMock(NamedStreet::class);
        $namedStreet
            ->method('getRoadName')
            ->willReturn('Rue Ardoin');

        $location = $this->createMock(Location::class);
        $location
            ->method('getUuid')
            ->willReturn('0658c568-dfbb-4c7a-a1dc-ff5a3ce13f16');
        $location
            ->method('getGeometry')
            ->willReturn('<polygon>');
        $location
            ->method('getNamedStreet')
            ->willReturn($namedStreet);

        $this->locationRepository
            ->expects(self::once())
            ->method('findAllWithPolygonGeometry')
            ->willReturn([$location]);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('findSectionsInArea')
            ->with('<polygon>', [RoadGeocoderInterface::HIGHWAY], true)
            ->willReturn('<sections>');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('f2c03654-4ad9-4eed-827d-dab4ebec5a29');

        $createdZone = $this->createMock(Zone::class);

        $this->zoneRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Zone(
                        uuid: 'f2c03654-4ad9-4eed-827d-dab4ebec5a29',
                        location: $location,
                        label: 'Rue Ardoin',
                        geometry: '<polygon>',
                    ),
                ),
            )
            ->willReturn($createdZone);

        $location
            ->expects(self::once())
            ->method('setZone')
            ->with($createdZone);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(RoadTypeEnum::ZONE->value, '<sections>');

        $this->namedStreetRepository
            ->expects(self::once())
            ->method('delete')
            ->with($namedStreet);

        $this->rawGeoJSONRepository
            ->expects(self::never())
            ->method('delete');

        $expectedResult = new ConvertPolygonsToZonesCommandResult(
            numLocations: 1,
            convertedLocationUuids: ['0658c568-dfbb-4c7a-a1dc-ff5a3ce13f16'],
            exceptions: [],
        );

        $this->assertEquals($expectedResult, ($this->createHandler())(new ConvertPolygonsToZonesCommand()));
    }

    public function testGeocodingFailure(): void
    {
        $exception = new GeocodingFailureException('Sections in area query has failed');

        $location = $this->createMock(Location::class);
        $location
            ->method('getUuid')
            ->willReturn('0658c568-dfbb-4c7a-a1dc-ff5a3ce13f16');
        $location
            ->method('getGeometry')
            ->willReturn('<polygon>');
        $location
            ->expects(self::never())
            ->method('setZone');
        $location
            ->expects(self::never())
            ->method('update');

        $this->locationRepository
            ->expects(self::once())
            ->method('findAllWithPolygonGeometry')
            ->willReturn([$location]);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('findSectionsInArea')
            ->willThrowException($exception);

        $this->zoneRepository
            ->expects(self::never())
            ->method('add');

        $this->rawGeoJSONRepository
            ->expects(self::never())
            ->method('delete');

        $expectedResult = new ConvertPolygonsToZonesCommandResult(
            numLocations: 1,
            convertedLocationUuids: [],
            exceptions: ['0658c568-dfbb-4c7a-a1dc-ff5a3ce13f16' => $exception],
        );

        $this->assertEquals($expectedResult, ($this->createHandler())(new ConvertPolygonsToZonesCommand()));
    }

    public function testDryRun(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->method('getUuid')
            ->willReturn('0658c568-dfbb-4c7a-a1dc-ff5a3ce13f16');
        $location
            ->expects(self::never())
            ->method('setZone');
        $location
            ->expects(self::never())
            ->method('update');

        $this->locationRepository
            ->expects(self::once())
            ->method('findAllWithPolygonGeometry')
            ->willReturn([$location]);

        $this->roadGeocoder
            ->expects(self::never())
            ->method('findSectionsInArea');

        $this->zoneRepository
            ->expects(self::never())
            ->method('add');

        $this->rawGeoJSONRepository
            ->expects(self::never())
            ->method('delete');

        $expectedResult = new ConvertPolygonsToZonesCommandResult(
            numLocations: 1,
            convertedLocationUuids: ['0658c568-dfbb-4c7a-a1dc-ff5a3ce13f16'],
            exceptions: [],
        );

        $this->assertEquals($expectedResult, ($this->createHandler())(new ConvertPolygonsToZonesCommand(dryRun: true)));
    }
}
