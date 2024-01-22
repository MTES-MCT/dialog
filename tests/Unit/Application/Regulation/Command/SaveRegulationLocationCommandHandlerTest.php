<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\DeleteMeasureCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommandHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\LocationNewRepositoryInterface;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveRegulationLocationCommandHandlerTest extends TestCase
{
    private $roadType;
    private $administrator;
    private $roadNumber;
    private $cityCode;
    private $cityLabel;
    private $roadName;
    private $fromHouseNumber;
    private $toHouseNumber;
    private $regulationOrder;
    private $regulationOrderRecord;
    private $commandBus;
    private $idFactory;
    private $locationRepository;
    private $locationNewRepository;
    private $geocoder;
    private $roadGeocoder;
    private $geometry;

    protected function setUp(): void
    {
        $this->roadType = 'lane';
        $this->cityCode = '44195';
        $this->cityLabel = 'Savenay';
        $this->administrator = null;
        $this->roadNumber = null;
        $this->roadName = 'Route du Grand Brossais';
        $this->fromHouseNumber = '15';
        $this->toHouseNumber = '37bis';
        $this->geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->locationNewRepository = $this->createMock(LocationNewRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->regulationOrder = $this->createMock(RegulationOrder::class);
        $this->geocoder = $this->createMock(GeocoderInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn(
                '4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8',
                '9dbbe0ce-2672-4f18-80b9-06d0102cb855',
            );

        $this->geocoder
            ->expects(self::exactly(2))
            ->method('computeCoordinates')
            ->willReturnOnConsecutiveCalls(
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            );

        $createdMeasure = $this->createMock(Measure::class);
        $createdLocation = $this->createMock(Location::class);
        $createdLocationNew = $this->createMock(LocationNew::class);

        $location = new Location(
            uuid: '4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8',
            regulationOrder: $this->regulationOrder,
            roadType: $this->roadType,
            cityCode: $this->cityCode,
            cityLabel: $this->cityLabel,
            administrator: $this->administrator,
            roadNumber: $this->roadNumber,
            roadName: $this->roadName,
            fromHouseNumber: $this->fromHouseNumber,
            toHouseNumber: $this->toHouseNumber,
            geometry: $this->geometry,
        );

        $locationNew = new LocationNew(
            uuid: '9dbbe0ce-2672-4f18-80b9-06d0102cb855',
            measure: $createdMeasure,
            roadType: $this->roadType,
            cityCode: $this->cityCode,
            cityLabel: $this->cityLabel,
            administrator: $this->administrator,
            roadNumber: $this->roadNumber,
            roadName: $this->roadName,
            fromHouseNumber: $this->fromHouseNumber,
            geometry: $this->geometry,
            toHouseNumber: $this->toHouseNumber,
        );

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->location = $createdLocation;
        $measureCommand->type = MeasureTypeEnum::ALTERNATE_ROAD->value;

        $createdLocation
            ->expects(self::once())
            ->method('addMeasure')
            ->with($createdMeasure);
        $createdLocation
            ->expects(self::once())
            ->method('getRoadType')
            ->willReturn($this->roadType);
        $createdLocation
            ->expects(self::once())
            ->method('getCityCode')
            ->willReturn($this->cityCode);
        $createdLocation
            ->expects(self::once())
            ->method('getCityLabel')
            ->willReturn($this->cityLabel);
        $createdLocation
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn($this->roadName);
        $createdLocation
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($this->geometry);
        $createdLocation
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn($this->fromHouseNumber);
        $createdLocation
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn($this->toHouseNumber);
        $createdMeasure
            ->expects(self::once())
            ->method('addLocation')
            ->with($locationNew);
        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($location))
            ->willReturn($createdLocation);
        $this->locationNewRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($locationNew))
            ->willReturn($createdLocationNew);
        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($measureCommand)
            ->willReturn($createdMeasure);

        $handler = new SaveRegulationLocationCommandHandler(
            $this->idFactory,
            $this->commandBus,
            $this->locationRepository,
            $this->locationNewRepository,
            $this->geocoder,
            $this->roadGeocoder,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord);
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityLabel = $this->cityLabel;
        $command->cityCode = $this->cityCode;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;
        $command->measures = [
            $measureCommand,
        ];

        $this->assertSame($createdLocation, $handler($command));
    }

    public function testCreateFullRoad(): void
    {
        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn(
                '4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8',
                '9dbbe0ce-2672-4f18-80b9-06d0102cb855',
            );

        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('Route du Grand Brossais', '44195')
            ->willReturn(
                json_encode(['type' => 'LineString', 'coordinates' => ['...']]),
            );

        $location = new Location(
            uuid: '4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8',
            regulationOrder: $this->regulationOrder,
            roadType: $this->roadType,
            cityCode: $this->cityCode,
            cityLabel: $this->cityLabel,
            administrator: $this->administrator,
            roadNumber: $this->roadNumber,
            roadName: $this->roadName,
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: json_encode(['type' => 'LineString', 'coordinates' => ['...']]),
        );

        $createdMeasure = $this->createMock(Measure::class);
        $createdLocation = $this->createMock(Location::class);

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->location = $createdLocation;
        $measureCommand->type = MeasureTypeEnum::ALTERNATE_ROAD->value;

        $createdLocation
            ->expects(self::once())
            ->method('addMeasure')
            ->with($createdMeasure);
        $this->locationRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($location))
            ->willReturn($createdLocation);
        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($measureCommand)
            ->willReturn($createdMeasure);

        $handler = new SaveRegulationLocationCommandHandler(
            $this->idFactory,
            $this->commandBus,
            $this->locationRepository,
            $this->locationNewRepository,
            $this->geocoder,
            $this->roadGeocoder,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord);
        $command->roadType = $this->roadType;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = null;
        $command->toHouseNumber = null;
        $command->measures = [
            $measureCommand,
        ];

        $this->assertSame($createdLocation, $handler($command));
    }

    public function testUpdate(): void
    {
        $locationNew = $this->createMock(LocationNew::class);
        $locationNew
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->roadType,
                $this->cityCode,
                $this->cityLabel,
                $this->administrator,
                $this->roadNumber,
                $this->roadName,
                $this->fromHouseNumber,
                $this->toHouseNumber,
                $this->geometry,
            );

        $measureToUpdate = $this->createMock(Measure::class);
        $measureToUpdate
            ->expects(self::once())
            ->method('getCreatedAt')
            ->willReturn(new \DateTimeImmutable('2023-06-01'));
        $measureToUpdate
            ->expects(self::once())
            ->method('getLocationNew')
            ->willReturn($locationNew);

        $measureToRemove = $this->createMock(Measure::class);
        $measureToRemove
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('0658d819-e69b-713c-8000-b30143acb13a');

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->roadType,
                $this->cityCode,
                $this->cityLabel,
                $this->administrator,
                $this->roadNumber,
                $this->roadName,
                $this->fromHouseNumber,
                $this->toHouseNumber,
                $this->geometry,
            );

        $location
            ->expects(self::once())
            ->method('getMeasures')
            ->willReturn([$measureToUpdate, $measureToRemove]);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->geocoder
            ->expects(self::exactly(2))
            ->method('computeCoordinates')
            ->willReturnOnConsecutiveCalls(
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            );

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $measureToUpdateCommand = new SaveMeasureCommand($measureToUpdate);
        $measureToUpdateCommand->location = $location;
        $measureToUpdateCommand->type = MeasureTypeEnum::ALTERNATE_ROAD->value;

        $matcher = self::exactly(2);
        $this->commandBus
            ->expects($matcher)
            ->method('handle')
            ->willReturnCallback(
                fn ($command) => match ($matcher->getInvocationCount()) {
                    1 => $this->assertEquals($measureToUpdateCommand, $command),
                    2 => $this->assertEquals(new DeleteMeasureCommand($measureToRemove), $command),
                },
            );

        $location
            ->expects(self::never())
            ->method('addMeasure');

        $location
            ->expects(self::once())
            ->method('removeMeasure')
            ->with($measureToRemove);

        $handler = new SaveRegulationLocationCommandHandler(
            $this->idFactory,
            $this->commandBus,
            $this->locationRepository,
            $this->locationNewRepository,
            $this->geocoder,
            $this->roadGeocoder,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord, $location);
        $command->roadType = $this->roadType;
        $command->cityCode = $this->cityCode;
        $command->cityLabel = $this->cityLabel;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;
        $command->measures = [
            $measureToUpdateCommand,
        ];

        $this->assertSame($location, $handler($command));
    }

    public function testHouseNumbersOptional(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->roadType,
                $this->cityCode,
                $this->cityLabel,
                $this->administrator,
                $this->roadNumber,
                $this->roadName,
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveRegulationLocationCommandHandler(
            $this->idFactory,
            $this->commandBus,
            $this->locationRepository,
            $this->locationNewRepository,
            $this->geocoder,
            $this->roadGeocoder,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord, $location);
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityLabel = $this->cityLabel;
        $command->cityCode = $this->cityCode;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = null;
        $command->toHouseNumber = null;

        $this->assertSame($location, $handler($command));
    }

    public function testHouseNumberOnOneSideOnly(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->roadType,
                $this->cityCode,
                $this->cityLabel,
                $this->administrator,
                $this->roadNumber,
                $this->roadName,
                '137',
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveRegulationLocationCommandHandler(
            $this->idFactory,
            $this->commandBus,
            $this->locationRepository,
            $this->locationNewRepository,
            $this->geocoder,
            $this->roadGeocoder,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord, $location);
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityLabel = $this->cityLabel;
        $command->cityCode = $this->cityCode;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = '137';
        $command->toHouseNumber = null;

        $this->assertSame($location, $handler($command));
    }

    public function testUpdateNoChangeDoesNotRecomputePoints(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::never())
            ->method('getRoadType');
        $location
        ->expects(self::never())
        ->method('getAdministrator');
        $location
        ->expects(self::never())
        ->method('getRoadNumber');
        $location
            ->expects(self::never())
            ->method('getCityLabel');
        $location
            ->expects(self::once())
            ->method('getCityCode')
            ->willReturn($this->cityCode);
        $location
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn($this->roadName);
        $location
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn($this->fromHouseNumber);
        $location
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($this->geometry);
        $location
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn($this->toHouseNumber);

        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->roadType,
                $this->cityCode,
                $this->cityLabel,
                $this->administrator,
                $this->roadNumber,
                $this->roadName,
                $this->fromHouseNumber,
                $this->toHouseNumber,
                $this->geometry,
            );

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $this->locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveRegulationLocationCommandHandler(
            $this->idFactory,
            $this->commandBus,
            $this->locationRepository,
            $this->locationNewRepository,
            $this->geocoder,
            $this->roadGeocoder,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord, $location);
        $command->roadType = $this->roadType;
        $command->administrator = $this->administrator;
        $command->roadNumber = $this->roadNumber;
        $command->cityLabel = $this->cityLabel;
        $command->cityCode = $this->cityCode;
        $command->roadName = $this->roadName;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $this->assertSame($location, $handler($command));
    }
}
