<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommandHandler;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadLine;
use App\Application\RoadLineSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveLocationCommandHandlerTest extends TestCase
{
    private string $roadType;
    private ?string $administrator;
    private ?string $roadNumber;
    private string $cityCode;
    private string $cityLabel;
    private string $roadName;
    private bool $isEntireStreet;
    private string $fromHouseNumber;
    private string $toHouseNumber;
    private string $geometry;
    private $idFactory;
    private $locationRepository;
    private $roadGeocoder;
    private $roadLineSectionMaker;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->roadLineSectionMaker = $this->createMock(RoadLineSectionMakerInterface::class);

        $this->roadType = 'lane';
        $this->administrator = null;
        $this->roadNumber = null;
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

        // $this->geocoder
        //     ->expects(self::exactly(2))
        //     ->method('computeCoordinates')
        //     ->willReturnOnConsecutiveCalls(
        //         Coordinates::fromLonLat(-1.935836, 47.347024),
        //         Coordinates::fromLonLat(-1.930973, 47.347917),
        //     );

        $roadLine = new RoadLine('geometry', 'id', $this->roadName, $this->cityCode);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with($this->roadName, $this->cityCode)
            ->willReturn($roadLine);

        $this->roadLineSectionMaker
            ->expects(self::once())
            ->method('computeRoadLineSection')
            ->with($roadLine, $this->fromHouseNumber, null, $this->toHouseNumber, null)
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
                        roadType: $this->roadType,
                        administrator: $this->administrator,
                        roadNumber: $this->roadNumber,
                        cityCode: $this->cityCode,
                        cityLabel: $this->cityLabel,
                        roadName: $this->roadName,
                        fromHouseNumber: $this->fromHouseNumber,
                        toHouseNumber: $this->toHouseNumber,
                        geometry: $this->geometry,
                        roadLineGeometry: $roadLine->geometry,
                        roadLineId: $roadLine->id,
                    ),
                ),
            )
            ->willReturn($createdLocation);

        $handler = new SaveLocationCommandHandler(
            $this->idFactory,
            $this->locationRepository,
            $this->roadGeocoder,
            $this->roadLineSectionMaker,
        );

        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $result = $handler($command);

        $this->assertSame($createdLocation, $result);
    }

    public function testCreateFullRoad(): void
    {
        $measure = $this->createMock(Measure::class);

        $roadLine = new RoadLine(
            json_encode(['type' => 'LineString', 'coordinates' => ['...']]),
            'test',
            $this->roadName,
            $this->cityCode,
        );

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('Route du Grand Brossais', '44195')
            ->willReturn($roadLine);

        $location = new Location(
            uuid: '4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8',
            measure: $measure,
            roadType: $this->roadType,
            administrator: $this->administrator,
            roadNumber: $this->roadNumber,
            cityCode: $this->cityCode,
            cityLabel: $this->cityLabel,
            roadName: $this->roadName,
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: json_encode(['type' => 'LineString', 'coordinates' => ['...']]),
            roadLineGeometry: $roadLine->geometry,
            roadLineId: $roadLine->id,
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
            $this->roadLineSectionMaker,
        );

        $command = new SaveLocationCommand();
        $command->measure = $measure;
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = null;
        $command->toHouseNumber = null;

        $this->assertSame($createdLocation, $handler($command));
    }

    // public function testHouseNumberOnOneSideOnly(): void
    // {
    //     $location = $this->createMock(Location::class);
    //     $location
    //         ->expects(self::once())
    //         ->method('update')
    //         ->with(
    //             $this->roadType,
    //             $this->administrator,
    //             $this->roadNumber,
    //             $this->cityCode,
    //             $this->cityLabel,
    //             $this->roadName,
    //             '137',
    //         );

    //     $this->idFactory
    //         ->expects(self::never())
    //         ->method('make');

    //     $this->geocoder
    //         ->expects(self::once())
    //         ->method('computeCoordinates')
    //         ->willReturn(Coordinates::fromLonLat(-1.935836, 47.347024));

    //     $this->roadGeocoder
    //         ->expects(self::once())
    //         ->method('computeRoadLine')
    //         ->with($this->roadName, $this->cityCode)
    //         ->willReturn(new RoadLine('geometry', 'id'));

    //     $firstPoint = Coordinates::fromLonLat(0, 0); // Values don't matter

    //     $this->geometryService
    //         ->expects(self::once())
    //         ->method('getFirstPointOfLinestring')
    //         ->with('geometry')
    //         ->willReturn($firstPoint);

    //     // (*) Simulate house number ordering opposite to line point ordering
    //     $this->geocoder
    //         ->expects(self::once())
    //         ->method('findHouseNumberOnRoad')
    //         ->with('id', $firstPoint)
    //         ->willReturn('156'); // (*)

    //     $this->geometryService
    //         ->expects(self::once())
    //         ->method('locatePointOnLine')
    //         ->willReturn(0.1); // (*) House number 137 is roughly at the beginning of first point is house number 156

    //     $this->geometryService
    //         ->expects(self::once())
    //         ->method('clipLine')
    //         ->with('geometry', 0, 0.1) // (*) Fraction of house number 156 -> Fraction of house number 137
    //         ->willReturn($this->geometry);

    //     $this->locationRepository
    //         ->expects(self::never())
    //         ->method('add');

    //     $handler = new SaveLocationCommandHandler(
    //         $this->idFactory,
    //         $this->locationRepository,
    //         $this->geocoder,
    //         $this->roadGeocoder,
    //         $this->geometryService,
    //     );

    //     $command = new SaveLocationCommand($location);
    //     $command->roadType = $this->roadType;
    //     $command->administrator = $this->administrator;
    //     $command->roadNumber = $this->roadNumber;
    //     $command->cityCode = $this->cityCode;
    //     $command->cityLabel = $this->cityLabel;
    //     $command->roadName = $this->roadName;
    //     $command->setIsEntireStreet(false); // Need to switch explicitly
    //     $command->fromHouseNumber = '137';
    //     $command->toHouseNumber = null;

    //     $this->assertSame($location, $handler($command));
    // }

    public function testUpdateNoChangeDoesNotRecomputePoints(): void
    {
        $roadLine = new RoadLine('geometry', 'id', $this->roadName, $this->cityCode);

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($this->roadType);
        $location
            ->expects(self::once())
            ->method('getAdministrator')
            ->willReturn($this->administrator);
        $location
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn($this->roadNumber);
        $location
            ->expects(self::once())
            ->method('getCityLabel')
            ->willReturn($this->cityLabel);
        $location
            ->expects(self::exactly(3))
            ->method('getCityCode')
            ->willReturn($this->cityCode);
        $location
            ->expects(self::exactly(3))
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
            ->expects(self::exactly(2))
            ->method('getRoadLine')
            ->willReturn($roadLine);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->roadType,
                $this->administrator,
                $this->roadNumber,
                $this->cityCode,
                $this->cityLabel,
                $this->roadName,
                $this->fromHouseNumber,
                $this->toHouseNumber,
                $this->geometry,
                $roadLine->geometry,
                $roadLine->id,
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
            $this->roadLineSectionMaker,
        );

        $command = new SaveLocationCommand($location);
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $this->assertSame($location, $handler($command));
    }

    // public function testCreateWithJunctions(): void
    // {
    //     $this->idFactory
    //         ->expects(self::once())
    //         ->method('make')
    //         ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

    //     $this->geocoder
    //         ->expects(self::exactly(2))
    //         ->method('computeJunctionCoordinates')
    //         ->willReturnOnConsecutiveCalls(
    //             Coordinates::fromLonLat(-1.935836, 47.347024),
    //             Coordinates::fromLonLat(-1.930973, 47.347917),
    //         );

    //     $this->geocoder
    //         ->expects(self::never())
    //         ->method('computeCoordinates');

    //     $createdLocation = $this->createMock(Location::class);
    //     $measure = $this->createMock(Measure::class);

    //     $this->locationRepository
    //         ->expects(self::once())
    //         ->method('add')
    //         ->with(
    //             $this->equalTo(
    //                 new Location(
    //                     uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
    //                     measure: $measure,
    //                     roadType: $this->roadType,
    //                     administrator: $this->administrator,
    //                     roadNumber: $this->roadNumber,
    //                     cityCode: $this->cityCode,
    //                     cityLabel: $this->cityLabel,
    //                     roadName: $this->roadName,
    //                     fromHouseNumber: null,
    //                     toHouseNumber: null,
    //                     geometry: $this->geometry,
    //                 ),
    //             ),
    //         )
    //         ->willReturn($createdLocation);

    //     $handler = new SaveLocationCommandHandler(
    //         $this->idFactory,
    //         $this->locationRepository,
    //         $this->geocoder,
    //         $this->roadGeocoder,
    //         $this->geometryService,
    //     );

    //     $command = new SaveLocationCommand();
    //     $command->measure = $measure;
    //     $command->roadType = $this->roadType;
    //     $command->administrator = $this->administrator;
    //     $command->roadNumber = $this->roadNumber;
    //     $command->cityCode = $this->cityCode;
    //     $command->cityLabel = $this->cityLabel;
    //     $command->roadName = $this->roadName;
    //     $command->fromRoadName = 'Route du début';
    //     $command->toRoadName = 'Route de la fin';

    //     $result = $handler($command);

    //     $this->assertSame($createdLocation, $result);
    // }
}
