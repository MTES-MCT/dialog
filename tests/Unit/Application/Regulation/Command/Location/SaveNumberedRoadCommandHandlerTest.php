<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommandHandler;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Repository\NumberedRoadRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveNumberedRoadCommandHandlerTest extends TestCase
{
    private ?string $administrator;
    private ?string $roadNumber;
    private string $geometry;
    private string $fromPointNumber;
    private string $fromSide;
    private int $fromAbscissa;
    private string $toPointNumber;
    private string $toSide;
    private int $toAbscissa;

    private MockObject $idFactory;
    private MockObject $locationRepository;
    private MockObject $roadGeocoder;
    private MockObject $numberedRoadRepository;
    private MockObject $roadSectionMaker;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->numberedRoadRepository = $this->createMock(NumberedRoadRepositoryInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->roadSectionMaker = $this->createMock(RoadSectionMakerInterface::class);

        $this->administrator = 'Département de Loire-Atlantique';
        $this->roadNumber = 'D12';
        $this->fromPointNumber = '1';
        $this->fromSide = 'U';
        $this->fromAbscissa = 0;
        $this->toPointNumber = '5';
        $this->toSide = 'U';
        $this->toAbscissa = 100;

        $this->geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);
    }

    public function testCreate(): void
    {
        $roadType = 'departmentalRoad';
        $fullDepartmentalRoadGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0', 'f40f1736-b9ea-4d08-9ebc-e6369102b5a9');

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
                        roadType: $roadType,
                        geometry: 'sectionGeometry',
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $this->numberedRoadRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new NumberedRoad(
                        uuid: 'f40f1736-b9ea-4d08-9ebc-e6369102b5a9',
                        location: $createdLocation,
                        administrator: $this->administrator,
                        roadNumber: $this->roadNumber,
                        fromPointNumber: $this->fromPointNumber,
                        fromSide: $this->fromSide,
                        fromAbscissa: $this->fromAbscissa,
                        toPointNumber: $this->toPointNumber,
                        toSide: $this->toSide,
                        toAbscissa: $this->toAbscissa,
                    ),
                ),
            );

        $handler = new SaveNumberedRoadCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->numberedRoadRepository,
            $this->roadGeocoder,
            $this->roadSectionMaker,
        );

        $command = new SaveNumberedRoadCommand();
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

    public function testCreateWithoutGeometryToRecompute(): void
    {
        $roadType = 'departmentalRoad';
        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0', 'f40f1736-b9ea-4d08-9ebc-e6369102b5a9');

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoad');

        $this->roadSectionMaker
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
                        roadType: $roadType,
                        geometry: 'sectionGeometry',
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $this->numberedRoadRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new NumberedRoad(
                        uuid: 'f40f1736-b9ea-4d08-9ebc-e6369102b5a9',
                        location: $createdLocation,
                        administrator: $this->administrator,
                        roadNumber: $this->roadNumber,
                        fromPointNumber: $this->fromPointNumber,
                        fromSide: $this->fromSide,
                        fromAbscissa: $this->fromAbscissa,
                        toPointNumber: $this->toPointNumber,
                        toSide: $this->toSide,
                        toAbscissa: $this->toAbscissa,
                    ),
                ),
            );

        $handler = new SaveNumberedRoadCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->numberedRoadRepository,
            $this->roadGeocoder,
            $this->roadSectionMaker,
        );

        $command = new SaveNumberedRoadCommand();
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
        $command->geometry = 'sectionGeometry';

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testUpdate(): void
    {
        $roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $fullDepartmentalRoadGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

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

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::never())
            ->method('getGeometry');

        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getFromPointNumber')
            ->willReturn('10'); // Changed
        $numberedRoad
            ->expects(self::once())
            ->method('getFromAbscissa')
            ->willReturn($this->fromAbscissa);
        $numberedRoad
            ->expects(self::once())
            ->method('getFromSide')
            ->willReturn($this->fromSide);
        $numberedRoad
            ->expects(self::once())
            ->method('getToPointNumber')
            ->willReturn('21'); // Changed
        $numberedRoad
            ->expects(self::once())
            ->method('getToAbscissa')
            ->willReturn($this->toAbscissa);
        $numberedRoad
            ->expects(self::once())
            ->method('getToSide')
            ->willReturn($this->toSide);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getAdministrator')
            ->willReturn($this->administrator);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getRoadNumber')
            ->willReturn($this->roadNumber);
        $numberedRoad
            ->expects(self::once())
            ->method('getLocation')
            ->willReturn($location);
        $numberedRoad
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->administrator,
                $this->roadNumber,
                $this->fromPointNumber,
                $this->fromSide,
                $this->fromAbscissa,
                $this->toPointNumber,
                $this->toSide,
                $this->toAbscissa,
            );

        $location
            ->expects(self::once())
            ->method('updateGeometry')
            ->with('sectionGeometry');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $this->numberedRoadRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveNumberedRoadCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->numberedRoadRepository,
            $this->roadGeocoder,
            $this->roadSectionMaker,
        );

        $command = new SaveNumberedRoadCommand($numberedRoad);
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

    public function testUpdateWithoutGeometryToRecompute(): void
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
            ->method('getGeometry')
            ->willReturn($fullDepartmentalRoadGeometry);

        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getFromPointNumber')
            ->willReturn($this->fromPointNumber);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getFromAbscissa')
            ->willReturn($this->fromAbscissa);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getFromSide')
            ->willReturn($this->fromSide);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getToPointNumber')
            ->willReturn($this->toPointNumber);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getToAbscissa')
            ->willReturn($this->toAbscissa);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getToSide')
            ->willReturn($this->toSide);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getAdministrator')
            ->willReturn($this->administrator);
        $numberedRoad
            ->expects(self::exactly(2))
            ->method('getRoadNumber')
            ->willReturn($this->roadNumber);
        $numberedRoad
            ->expects(self::once())
            ->method('getLocation')
            ->willReturn($location);
        $numberedRoad
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->administrator,
                $this->roadNumber,
                $this->fromPointNumber,
                $this->fromSide,
                $this->fromAbscissa,
                $this->toPointNumber,
                $this->toSide,
                $this->toAbscissa,
            );

        $location
            ->expects(self::once())
            ->method('updateGeometry')
            ->with($fullDepartmentalRoadGeometry);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $this->numberedRoadRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveNumberedRoadCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->numberedRoadRepository,
            $this->roadGeocoder,
            $this->roadSectionMaker,
        );

        $command = new SaveNumberedRoadCommand($numberedRoad);
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
}
