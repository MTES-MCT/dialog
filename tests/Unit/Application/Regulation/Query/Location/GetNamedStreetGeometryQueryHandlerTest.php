<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\LaneSectionMakerInterface;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Query\Location\GetNamedStreetGeometryQuery;
use App\Application\Regulation\Query\Location\GetNamedStreetGeometryQueryHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetNamedStreetGeometryQueryHandlerTest extends TestCase
{
    private string $cityCode;
    private string $cityLabel;
    private string $roadName;
    private string $direction;
    private string $fromHouseNumber;
    private string $toHouseNumber;
    private string $geometry;
    private MockObject $roadGeocoder;
    private MockObject $laneSectionMaker;

    public function setUp(): void
    {
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->laneSectionMaker = $this->createMock(LaneSectionMakerInterface::class);

        $this->cityCode = '44195';
        $this->cityLabel = 'Savenay';
        $this->roadName = 'Route du Grand Brossais';
        $this->fromHouseNumber = '15';
        $this->toHouseNumber = '37bis';
        $this->direction = DirectionEnum::BOTH->value;
        $this->geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);
    }

    public function testGet(): void
    {
        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with($this->roadName, $this->cityCode)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with('fullLaneGeometry', $this->roadName, $this->cityCode, $this->direction, null, $this->fromHouseNumber, null, null, $this->toHouseNumber, null)
            ->willReturn($this->geometry);

        $handler = new GetNamedStreetGeometryQueryHandler(
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $saveNamedStreetCommand = new SaveNamedStreetCommand();
        $saveNamedStreetCommand->roadType = RoadTypeEnum::LANE->value;
        $saveNamedStreetCommand->direction = $this->direction;
        $saveNamedStreetCommand->cityCode = $this->cityCode;
        $saveNamedStreetCommand->cityLabel = $this->cityLabel;
        $saveNamedStreetCommand->roadName = $this->roadName;
        $saveNamedStreetCommand->fromHouseNumber = $this->fromHouseNumber;
        $saveNamedStreetCommand->toHouseNumber = $this->toHouseNumber;

        $result = $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand));

        $this->assertSame($this->geometry, $result);
    }

    public function testGetWithGeometry(): void
    {
        $location = $this->createMock(Location::class);

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoadLine');

        $this->laneSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $handler = new GetNamedStreetGeometryQueryHandler(
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $saveNamedStreetCommand = new SaveNamedStreetCommand();
        $result = $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand, $location, $this->geometry));

        $this->assertSame($this->geometry, $result);
    }

    public function testGetWithLocationToRecompute(): void
    {
        $location = $this->createMock(Location::class);
        $namedStreet = $this->createMock(NamedStreet::class);
        $namedStreet
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn('Route de Paris'); // Changed
        $namedStreet
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn($this->fromHouseNumber);
        $namedStreet
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn($this->toHouseNumber);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with($this->roadName, $this->cityCode)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with('fullLaneGeometry', $this->roadName, $this->cityCode, $this->direction, null, $this->fromHouseNumber, null, null, $this->toHouseNumber, null)
            ->willReturn($this->geometry);

        $handler = new GetNamedStreetGeometryQueryHandler(
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $saveNamedStreetCommand = new SaveNamedStreetCommand($namedStreet);
        $saveNamedStreetCommand->roadType = RoadTypeEnum::LANE->value;
        $saveNamedStreetCommand->cityCode = $this->cityCode;
        $saveNamedStreetCommand->direction = $this->direction;
        $saveNamedStreetCommand->cityLabel = $this->cityLabel;
        $saveNamedStreetCommand->roadName = $this->roadName;
        $saveNamedStreetCommand->fromHouseNumber = $this->fromHouseNumber;
        $saveNamedStreetCommand->toHouseNumber = $this->toHouseNumber;
        $result = $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand, $location));

        $this->assertSame($this->geometry, $result);
    }

    public function testGetWithLocationToNotRecompute(): void
    {
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn($this->geometry);

        $namedStreet = $this->createMock(NamedStreet::class);
        $namedStreet
            ->expects(self::exactly(2))
            ->method('getRoadName')
            ->willReturn($this->roadName);
        $namedStreet
            ->expects(self::exactly(2))
            ->method('getCityCode')
            ->willReturn($this->cityCode);
        $namedStreet
            ->expects(self::once())
            ->method('getCityLabel')
            ->willReturn($this->cityLabel);
        $namedStreet
            ->expects(self::exactly(2))
            ->method('getFromHouseNumber')
            ->willReturn($this->fromHouseNumber);
        $namedStreet
            ->expects(self::exactly(2))
            ->method('getToHouseNumber')
            ->willReturn($this->toHouseNumber);

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoadLine');

        $this->laneSectionMaker
            ->expects(self::never())
            ->method('computeSection');

        $handler = new GetNamedStreetGeometryQueryHandler(
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $saveNamedStreetCommand = new SaveNamedStreetCommand($namedStreet);
        $saveNamedStreetCommand->roadType = RoadTypeEnum::LANE->value;
        $saveNamedStreetCommand->cityCode = $this->cityCode;
        $saveNamedStreetCommand->cityLabel = $this->cityLabel;
        $saveNamedStreetCommand->roadName = $this->roadName;
        $saveNamedStreetCommand->fromHouseNumber = $this->fromHouseNumber;
        $saveNamedStreetCommand->toHouseNumber = $this->toHouseNumber;
        $result = $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand, $location));

        $this->assertSame($this->geometry, $result);
    }

    public function testGetWithCoords(): void
    {
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
            ->with('fullLaneGeometry', $this->roadName, $this->cityCode, $this->direction, $fromCoords, null, null, $toCoords, null, null)
            ->willReturn($this->geometry);

        $handler = new GetNamedStreetGeometryQueryHandler(
            $this->roadGeocoder,
            $this->laneSectionMaker,
        );

        $saveNamedStreetCommand = new SaveNamedStreetCommand();
        $saveNamedStreetCommand->roadType = RoadTypeEnum::LANE->value;
        $saveNamedStreetCommand->direction = $this->direction;
        $saveNamedStreetCommand->cityCode = $this->cityCode;
        $saveNamedStreetCommand->cityLabel = $this->cityLabel;
        $saveNamedStreetCommand->roadName = $this->roadName;
        $saveNamedStreetCommand->fromCoords = $fromCoords;
        $saveNamedStreetCommand->toCoords = $toCoords;

        $result = $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand));

        $this->assertSame($this->geometry, $result);
    }
}
