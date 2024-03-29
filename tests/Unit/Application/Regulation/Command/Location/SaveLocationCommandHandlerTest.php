<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Exception\GeocodingFailureException;
use App\Application\IdFactoryInterface;
use App\Application\LaneSectionMakerInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommandHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveLocationCommandHandlerTest extends TestCase
{
    private ?string $administrator;
    private ?string $roadNumber;
    private string $cityCode;
    private string $cityLabel;
    private string $roadName;
    private string $fromHouseNumber;
    private string $toHouseNumber;
    private string $geometry;
    private $idFactory;
    private $locationRepository;
    private $roadGeocoder;
    private $laneSectionMaker;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->laneSectionMaker = $this->createMock(LaneSectionMakerInterface::class);

        $this->administrator = 'Département de Loire-Atlantique';
        $this->roadNumber = 'D12';
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

    public function testCreateRoadSection(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

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

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Location(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        roadType: RoadTypeEnum::LANE->value,
                        administrator: null,
                        roadNumber: null,
                        cityCode: $this->cityCode,
                        cityLabel: $this->cityLabel,
                        roadName: $this->roadName,
                        fromHouseNumber: $this->fromHouseNumber,
                        toHouseNumber: $this->toHouseNumber,
                        geometry: $this->geometry,
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveLocationCommand();
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

    public function testCreateFullLane(): void
    {
        $measure = $this->createMock(Measure::class);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('Route du Grand Brossais', '44195')
            ->willReturn($this->geometry);

        $location = new Location(
            uuid: '4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8',
            measure: $measure,
            roadType: RoadTypeEnum::LANE->value,
            administrator: null,
            roadNumber: null,
            cityCode: $this->cityCode,
            cityLabel: $this->cityLabel,
            roadName: $this->roadName,
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: $this->geometry,
        );

        $createdLocation = $this->createMock(Location::class);

        $measure
            ->expects(self::once())
            ->method('addLocation')
            ->with($createdLocation);
        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($location))
            ->willReturn($createdLocation);

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveLocationCommand();
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

    public function testUpdateNoChangeDoesNotRecomputePoints(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn(RoadTypeEnum::LANE->value);
        $location
            ->expects(self::once())
            ->method('getAdministrator')
            ->willReturn(null);
        $location
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn(null);
        $location
            ->expects(self::once())
            ->method('getCityLabel')
            ->willReturn($this->cityLabel);
        $location
            ->expects(self::exactly(2))
            ->method('getCityCode')
            ->willReturn($this->cityCode);
        $location
            ->expects(self::exactly(2))
            ->method('getRoadName')
            ->willReturn($this->roadName);
        $location
            ->expects(self::exactly(2))
            ->method('getFromHouseNumber')
            ->willReturn($this->fromHouseNumber);
        $location
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn($this->geometry);
        $location
            ->expects(self::exactly(2))
            ->method('getToHouseNumber')
            ->willReturn($this->toHouseNumber);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                RoadTypeEnum::LANE->value,
                null,
                null,
                $this->cityCode,
                $this->cityLabel,
                $this->roadName,
                $this->fromHouseNumber,
                $this->toHouseNumber,
                $this->geometry,
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveLocationCommand($location);
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $this->assertSame($location, $handler($command));
    }

    public function testUpdateDepartmentalRoadNoRecompute(): void
    {
        $roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $roadNumber = 'D12';
        $administrator = 'Ain';
        $departmentalRoadGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoadLine');

        $this->laneSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $this->roadGeocoder
            ->expects(self::never())
            ->method('findDepartmentalRoads');

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($roadType);
        $location
            ->expects(self::exactly(2))
            ->method('getAdministrator')
            ->willReturn($administrator);
        $location
            ->expects(self::exactly(2))
            ->method('getRoadNumber')
            ->willReturn($roadNumber);
        $location
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn($departmentalRoadGeometry);

        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $roadType,
                $administrator,
                $roadNumber,
                null,
                null,
                null,
                null,
                null,
                $departmentalRoadGeometry,
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveLocationCommand($location);
        $command->roadType = $roadType;
        $command->administrator = $administrator;
        $command->roadNumber = $roadNumber;
        $this->assertSame($location, $handler($command));
    }

    public function testUpdateDepartmentalRoadRecomputeWithGivenGeometry(): void
    {
        $roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $roadNumber = 'D12';
        $newRoadNumber = 'D13';
        $administrator = 'Ain';
        $departmentalRoadGeometry = 'geometry';

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoadLine');

        $this->laneSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $this->roadGeocoder
            ->expects(self::never())
            ->method('findDepartmentalRoads');

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($roadType);
        $location
            ->expects(self::once())
            ->method('getAdministrator')
            ->willReturn($administrator);
        $location
            ->expects(self::exactly(2))
            ->method('getRoadNumber')
            ->willReturn($roadNumber);
        $location
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($departmentalRoadGeometry);

        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $roadType,
                $administrator,
                $newRoadNumber,
                null,
                null,
                null,
                null,
                null,
                $departmentalRoadGeometry,
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveLocationCommand($location);
        $command->roadType = $roadType;
        $command->administrator = $administrator;
        $command->roadNumber = $newRoadNumber;
        $command->departmentalRoadGeometry = $departmentalRoadGeometry;
        $this->assertSame($location, $handler($command));
    }

    public function testCreateDepartmentalRoad(): void
    {
        $roadType = 'departmentalRoad';
        $roadNumber = 'D12';
        $administrator = 'Ain';
        $departmentalRoadGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Location(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        roadType: $roadType,
                        administrator: $administrator,
                        roadNumber: $roadNumber,
                        cityCode: null,
                        cityLabel: null,
                        roadName: null,
                        fromHouseNumber: null,
                        toHouseNumber: null,
                        geometry: $departmentalRoadGeometry,
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = $roadType;
        $command->administrator = $administrator;
        $command->roadNumber = $roadNumber;
        $command->departmentalRoadGeometry = $departmentalRoadGeometry;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateDepartmentalRoadWithoutGeometry(): void
    {
        $roadType = 'departmentalRoad';
        $roadNumber = 'D12';
        $administrator = 'Ain';
        $departmentalRoadGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('findDepartmentalRoads')
            ->with($roadNumber, $administrator)
            ->willReturn([
                [
                    'roadNumber' => $roadNumber,
                    'geometry' => $departmentalRoadGeometry,
                ],
            ]);

        $createdLocation = $this->createMock(Location::class);
        $measure = $this->createMock(Measure::class);

        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Location(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        roadType: $roadType,
                        administrator: $administrator,
                        roadNumber: $roadNumber,
                        cityCode: null,
                        cityLabel: null,
                        roadName: null,
                        fromHouseNumber: null,
                        toHouseNumber: null,
                        geometry: $departmentalRoadGeometry,
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = $roadType;
        $command->administrator = $administrator;
        $command->roadNumber = $roadNumber;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateDepartmentalRoadWithGeocodingFailureException(): void
    {
        $this->expectException(GeocodingFailureException::class);

        $roadType = 'departmentalRoad';
        $roadNumber = 'D12';
        $administrator = 'Ain';

        $this->idFactory
            ->expects(self::never())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('findDepartmentalRoads')
            ->with($roadNumber, $administrator)
            ->willReturn([]);

        $measure = $this->createMock(Measure::class);

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = $roadType;
        $command->administrator = $administrator;
        $command->roadNumber = $roadNumber;

        $handler($command);
    }

    public function testCreateWithCoords(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

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
                        administrator: null,
                        roadNumber: null,
                        cityCode: $this->cityCode,
                        cityLabel: $this->cityLabel,
                        roadName: $this->roadName,
                        fromHouseNumber: null,
                        toHouseNumber: null,
                        geometry: $this->geometry,
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $command = new SaveLocationCommand();
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
}
