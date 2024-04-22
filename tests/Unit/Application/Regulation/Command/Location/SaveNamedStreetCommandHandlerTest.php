<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\LaneSectionMakerInterface;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommandHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveNamedStreetCommandHandlerTest extends TestCase
{
    private string $cityCode;
    private string $cityLabel;
    private string $roadName;
    private string $fromHouseNumber;
    private string $toHouseNumber;
    private string $geometry;

    private MockObject $idFactory;
    private MockObject $locationRepository;
    private MockObject $namedStreetRepository;
    private MockObject $roadGeocoder;
    private MockObject $laneSectionMaker;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->namedStreetRepository = $this->createMock(NamedStreetRepositoryInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->laneSectionMaker = $this->createMock(LaneSectionMakerInterface::class);

        $this->cityCode = '44195';
        $this->cityLabel = 'Savenay';
        $this->roadName = 'Route du Grand Brossais';
        $this->fromHouseNumber = '15';
        $this->toHouseNumber = '37bis';
        $this->geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0', 'f2c03654-4ad9-4eed-827d-dab4ebec5a29');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with($this->roadName, $this->cityCode)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with('fullLaneGeometry', $this->roadName, $this->cityCode, null, $this->fromHouseNumber, null, null, $this->toHouseNumber, null)
            ->willReturn($this->geometry);

        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Location(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        roadType: RoadTypeEnum::LANE->value,
                        geometry: $this->geometry,
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $this->namedStreetRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new NamedStreet(
                        uuid: 'f2c03654-4ad9-4eed-827d-dab4ebec5a29',
                        location: $createdLocation,
                        cityCode: $this->cityCode,
                        cityLabel: $this->cityLabel,
                        roadName: $this->roadName,
                        fromHouseNumber: $this->fromHouseNumber,
                        toHouseNumber: $this->toHouseNumber,
                    ),
                ),
            );

        $handler = new SaveNamedStreetCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->namedStreetRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveNamedStreetCommand();
        $command->measure = $measure;
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateWithoutGeometryToRecompute(): void
    {
        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0', 'f2c03654-4ad9-4eed-827d-dab4ebec5a29');

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoadLine');

        $this->laneSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);
        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Location(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        roadType: RoadTypeEnum::LANE->value,
                        geometry: $this->geometry,
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $this->namedStreetRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new NamedStreet(
                        uuid: 'f2c03654-4ad9-4eed-827d-dab4ebec5a29',
                        location: $createdLocation,
                        cityCode: $this->cityCode,
                        cityLabel: $this->cityLabel,
                        roadName: $this->roadName,
                        fromHouseNumber: $this->fromHouseNumber,
                        toHouseNumber: $this->toHouseNumber,
                    ),
                ),
            );

        $handler = new SaveNamedStreetCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->namedStreetRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveNamedStreetCommand();
        $command->measure = $measure;
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;
        $command->geometry = $this->geometry;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateFullLane(): void
    {
        $measure = $this->createMock(Measure::class);

        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn('4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8', 'fa4012ad-0c4b-40aa-8b51-6c1b0da6d55e');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('Route du Grand Brossais', '44195')
            ->willReturn($this->geometry);

        $this->laneSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $createdLocation = $this->createMock(Location::class);
        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo(
                new Location(
                    uuid: '4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8',
                    measure: $measure,
                    roadType: RoadTypeEnum::LANE->value,
                    geometry: $this->geometry,
                ),
            ))
            ->willReturn($createdLocation);

        $this->namedStreetRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo(
                new NamedStreet(
                    uuid: 'fa4012ad-0c4b-40aa-8b51-6c1b0da6d55e',
                    location: $createdLocation,
                    cityCode: $this->cityCode,
                    cityLabel: $this->cityLabel,
                    roadName: $this->roadName,
                    fromHouseNumber: null,
                    toHouseNumber: null,
                ),
            ));

        $handler = new SaveNamedStreetCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->namedStreetRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveNamedStreetCommand();
        $command->measure = $measure;
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->setIsEntireStreet(true);
        $command->fromHouseNumber = null;
        $command->toHouseNumber = null;

        $this->assertSame($createdLocation, $handler($command));
    }

    public function testCreateWithCoords(): void
    {
        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0', 'c2f81933-6c5b-4e62-aab3-b9243540d7b2');

        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);

        $fromCoords = Coordinates::fromLonLat(-1.935836, 47.347024);
        $toCoords = Coordinates::fromLonLat(-1.930973, 47.347917);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with($this->roadName, $this->cityCode)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with('fullLaneGeometry', $this->roadName, $this->cityCode, $fromCoords, null, null, $toCoords, null, null)
            ->willReturn($this->geometry);

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Location(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        roadType: RoadTypeEnum::LANE->value,
                        geometry: $this->geometry,
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $this->namedStreetRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new NamedStreet(
                        uuid: 'c2f81933-6c5b-4e62-aab3-b9243540d7b2',
                        location: $createdLocation,
                        cityCode: $this->cityCode,
                        cityLabel: $this->cityLabel,
                        roadName: $this->roadName,
                        fromHouseNumber: null,
                        toHouseNumber: null,
                    ),
                ),
            );

        $handler = new SaveNamedStreetCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->namedStreetRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveNamedStreetCommand();
        $command->measure = $measure;
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromCoords = $fromCoords;
        $command->toCoords = $toCoords;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testUpdateNoChangeDoesNotRecomputePoints(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($this->geometry);
        $location
            ->expects(self::once())
            ->method('updateGeometry')
            ->with($this->geometry);

        $namedStreet = $this->createMock(NamedStreet::class);
        $namedStreet
            ->expects(self::once())
            ->method('getLocation')
            ->willReturn($location);
        $namedStreet
            ->expects(self::once())
            ->method('getCityLabel')
            ->willReturn($this->cityLabel);
        $namedStreet
            ->expects(self::exactly(2))
            ->method('getCityCode')
            ->willReturn($this->cityCode);
        $namedStreet
            ->expects(self::exactly(2))
            ->method('getRoadName')
            ->willReturn($this->roadName);
        $namedStreet
            ->expects(self::exactly(2))
            ->method('getFromHouseNumber')
            ->willReturn($this->fromHouseNumber);
        $namedStreet
            ->expects(self::exactly(2))
            ->method('getToHouseNumber')
            ->willReturn($this->toHouseNumber);

        $namedStreet
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->cityCode,
                $this->cityLabel,
                $this->roadName,
                $this->fromHouseNumber,
                $this->toHouseNumber,
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $this->namedStreetRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveNamedStreetCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->namedStreetRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveNamedStreetCommand($namedStreet);
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $this->assertSame($location, $handler($command));
    }
}
