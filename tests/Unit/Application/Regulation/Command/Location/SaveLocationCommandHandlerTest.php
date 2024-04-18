<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\LaneSectionMakerInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommandHandler;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
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
    private string $fromPointNumber;
    private string $fromSide;
    private int $fromAbscissa;
    private string $toPointNumber;
    private string $toSide;
    private int $toAbscissa;

    private $idFactory;
    private $locationRepository;
    private $roadGeocoder;
    private $laneSectionMaker;
    private $roadSectionMaker;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->laneSectionMaker = $this->createMock(LaneSectionMakerInterface::class);
        $this->roadSectionMaker = $this->createMock(RoadSectionMakerInterface::class);

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

        // Departmental road
        $this->fromPointNumber = '1';
        $this->fromSide = 'U';
        $this->fromAbscissa = 0;
        $this->toPointNumber = '5';
        $this->toSide = 'U';
        $this->toAbscissa = 100;
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
                        fromPointNumber: null,
                        fromSide: null,
                        fromAbscissa: null,
                        toPointNumber: null,
                        toSide: null,
                        toAbscissa: null,
                        roadGeometry: 'fullLaneGeometry',
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
            $this->roadSectionMaker,
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
            fromPointNumber: null,
            fromSide: null,
            fromAbscissa: null,
            toPointNumber: null,
            toSide: null,
            toAbscissa: null,
            roadGeometry: $this->geometry,
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
            $this->roadSectionMaker,
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
            ->method('getRoadGeometry')
            ->willReturn('roadGeometry');
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
                $this->cityCode,
                $this->cityLabel,
                $this->roadName,
                $this->fromHouseNumber,
                $this->toHouseNumber,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'roadGeometry',
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
            $this->roadSectionMaker,
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
        $fullDepartmentalRoadGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $this->roadSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoad');

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($roadType);
        $location
            ->expects(self::exactly(2))
            ->method('getFromPointNumber')
            ->willReturn($this->fromPointNumber);
        $location
            ->expects(self::exactly(2))
            ->method('getFromAbscissa')
            ->willReturn($this->fromAbscissa);
        $location
            ->expects(self::exactly(2))
            ->method('getFromSide')
            ->willReturn($this->fromSide);
        $location
            ->expects(self::exactly(2))
            ->method('getToPointNumber')
            ->willReturn($this->toPointNumber);
        $location
            ->expects(self::exactly(2))
            ->method('getToAbscissa')
            ->willReturn($this->toAbscissa);
        $location
            ->expects(self::exactly(2))
            ->method('getToSide')
            ->willReturn($this->toSide);
        $location
            ->expects(self::exactly(2))
            ->method('getAdministrator')
            ->willReturn($this->administrator);
        $location
            ->expects(self::exactly(2))
            ->method('getRoadNumber')
            ->willReturn($this->roadNumber);
        $location
            ->expects(self::exactly(2))
            ->method('getRoadGeometry')
            ->willReturn($fullDepartmentalRoadGeometry);
        $location
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn($fullDepartmentalRoadGeometry);

        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $roadType,
                null,
                null,
                null,
                null,
                null,
                $this->administrator,
                $this->roadNumber,
                $this->fromPointNumber,
                $this->fromSide,
                $this->fromAbscissa,
                $this->toPointNumber,
                $this->toSide,
                $this->toAbscissa,
                $fullDepartmentalRoadGeometry,
                $fullDepartmentalRoadGeometry,
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
            $this->roadSectionMaker,
        );

        $command = new SaveLocationCommand($location);
        $command->roadType = $roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->fromPointNumber = $this->fromPointNumber;
        $command->fromSide = $this->fromSide;
        $command->fromAbscissa = $this->fromAbscissa;
        $command->toPointNumber = $this->toPointNumber;
        $command->toSide = $this->toSide;
        $command->toAbscissa = $this->toAbscissa;
        $this->assertSame($location, $handler($command));
    }

    public function testCreateDepartmentalRoad(): void
    {
        $roadType = 'departmentalRoad';
        $fullDepartmentalRoadGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->with($this->roadNumber, $this->administrator)
            ->willReturn($fullDepartmentalRoadGeometry);

        $this->roadSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                $fullDepartmentalRoadGeometry,
                $this->administrator,
                $this->roadNumber,
                $this->fromPointNumber,
                $this->fromSide,
                $this->fromAbscissa,
                $this->toPointNumber,
                $this->toSide,
                $this->toAbscissa,
            )
            ->willReturn('sectionGeometry');

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
                        cityCode: null,
                        cityLabel: null,
                        roadName: null,
                        fromHouseNumber: null,
                        toHouseNumber: null,
                        administrator: $this->administrator,
                        roadNumber: $this->roadNumber,
                        fromPointNumber: $this->fromPointNumber,
                        fromSide: $this->fromSide,
                        fromAbscissa: $this->fromAbscissa,
                        toPointNumber: $this->toPointNumber,
                        toSide: $this->toSide,
                        toAbscissa: $this->toAbscissa,
                        roadGeometry: $fullDepartmentalRoadGeometry,
                        geometry: 'sectionGeometry',
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->laneSectionMaker,
            $this->roadSectionMaker,
        );

        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = $roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->fromPointNumber = $this->fromPointNumber;
        $command->fromSide = $this->fromSide;
        $command->fromAbscissa = $this->fromAbscissa;
        $command->toPointNumber = $this->toPointNumber;
        $command->toSide = $this->toSide;
        $command->toAbscissa = $this->toAbscissa;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
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
                        fromPointNumber: null,
                        fromSide: null,
                        fromAbscissa: null,
                        toPointNumber: null,
                        toSide: null,
                        toAbscissa: null,
                        roadGeometry: 'fullLaneGeometry',
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
            $this->roadSectionMaker,
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
