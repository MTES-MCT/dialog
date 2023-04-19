<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommandHandler;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeometryFormatter;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveRegulationLocationCommandHandlerTest extends TestCase
{
    private $address;
    private $fromHouseNumber;
    private $fromPoint;
    private $toHouseNumber;
    private $toPoint;
    private $regulationOrder;
    private $regulationOrderRecord;

    protected function setUp(): void
    {
        $this->address = 'Route du Grand Brossais 44260 Savenay';
        $this->fromHouseNumber = '15';
        $this->fromPoint = 'POINT(-1.935836 47.347024)';
        $this->toHouseNumber = '37bis';
        $this->toPoint = 'POINT(-1.930973 47.347917)';

        $this->regulationOrder = $this->createMock(RegulationOrder::class);
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);
    }

    public function testCreate(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8');

        $geocoder = $this->createMock(GeocoderInterface::class);
        $geocoder
            ->expects(self::exactly(2))
            ->method('computeCoordinates')
            ->willReturnOnConsecutiveCalls(
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            );

        $geometryFormatter = $this->createMock(GeometryFormatter::class);
        $geometryFormatter
            ->expects(self::exactly(2))
            ->method('formatPoint')
            ->willReturnOnConsecutiveCalls(
                'POINT(-1.935836 47.347024)',
                'POINT(-1.930973 47.347917)',
            );

        $location = new Location(
            uuid: '4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8',
            regulationOrder: $this->regulationOrder,
            address: $this->address,
            fromHouseNumber: $this->fromHouseNumber,
            fromPoint: $this->fromPoint,
            toHouseNumber: $this->toHouseNumber,
            toPoint: $this->toPoint,
        );

        $createdLocation = $this->createMock(Location::class);
        $createdLocation
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('73504e1a-45a1-4993-b82a-1189500715db');
        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $locationRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($location))
            ->willReturn($createdLocation);

        $handler = new SaveRegulationLocationCommandHandler(
            $idFactory,
            $locationRepository,
            $geocoder,
            $geometryFormatter,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord);
        $command->address = $this->address;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $this->assertSame('73504e1a-45a1-4993-b82a-1189500715db', $handler($command));
    }

    public function testUpdate(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('73504e1a-45a1-4993-b82a-1189500715db');
        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->address,
                $this->fromHouseNumber,
                $this->fromPoint,
                $this->toHouseNumber,
                $this->toPoint,
            );

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $idFactory
            ->expects(self::never())
            ->method('make');

        $geocoder = $this->createMock(GeocoderInterface::class);
        $geocoder
            ->expects(self::exactly(2))
            ->method('computeCoordinates')
            ->willReturnOnConsecutiveCalls(
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            );

        $geometryFormatter = $this->createMock(GeometryFormatter::class);
        $geometryFormatter
            ->expects(self::exactly(2))
            ->method('formatPoint')
            ->willReturnOnConsecutiveCalls(
                'POINT(-1.935836 47.347024)',
                'POINT(-1.930973 47.347917)',
            );

        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveRegulationLocationCommandHandler(
            $idFactory,
            $locationRepository,
            $geocoder,
            $geometryFormatter,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord, $location);
        $command->address = $this->address;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $this->assertSame('73504e1a-45a1-4993-b82a-1189500715db', $handler($command));
    }

    public function testHouseNumbersOptional(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('73504e1a-45a1-4993-b82a-1189500715db');
        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->address,
            );

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $idFactory
            ->expects(self::never())
            ->method('make');

        $geocoder = $this->createMock(GeocoderInterface::class);
        $geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $geometryFormatter = $this->createMock(GeometryFormatter::class);
        $geometryFormatter
            ->expects(self::never())
            ->method('formatPoint');

        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveRegulationLocationCommandHandler(
            $idFactory,
            $locationRepository,
            $geocoder,
            $geometryFormatter,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord, $location);
        $command->address = $this->address;
        $command->fromHouseNumber = null;
        $command->toHouseNumber = null;

        $this->assertSame('73504e1a-45a1-4993-b82a-1189500715db', $handler($command));
    }

    public function testUpdateNoChangeDoesNotRecomputePoints(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('73504e1a-45a1-4993-b82a-1189500715db');
        $location
            ->expects(self::once())
            ->method('getAddress')
            ->willReturn($this->address);
        $location
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn($this->fromHouseNumber);
        $location
            ->expects(self::once())
            ->method('getFromPoint')
            ->willReturn($this->fromPoint);
        $location
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn($this->toHouseNumber);
        $location
            ->expects(self::once())
            ->method('getToPoint')
            ->willReturn($this->toPoint);

        $location
            ->expects(self::once())
            ->method('update')
            ->with(
                $this->address,
                $this->fromHouseNumber,
                $this->fromPoint,
                $this->toHouseNumber,
                $this->toPoint,
            );

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $idFactory
            ->expects(self::never())
            ->method('make');

        $geocoder = $this->createMock(GeocoderInterface::class);
        $geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $geometryFormatter = $this->createMock(GeometryFormatter::class);
        $geometryFormatter
            ->expects(self::never())
            ->method('formatPoint');

        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $locationRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveRegulationLocationCommandHandler(
            $idFactory,
            $locationRepository,
            $geocoder,
            $geometryFormatter,
        );

        $command = new SaveRegulationLocationCommand($this->regulationOrderRecord, $location);
        $command->address = $this->address;
        $command->fromHouseNumber = $this->fromHouseNumber;
        $command->toHouseNumber = $this->toHouseNumber;

        $this->assertSame('73504e1a-45a1-4993-b82a-1189500715db', $handler($command));
    }
}
