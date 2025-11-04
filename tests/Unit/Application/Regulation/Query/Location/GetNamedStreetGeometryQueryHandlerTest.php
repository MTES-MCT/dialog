<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\Exception\GeocodingFailureException;
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
use PHPUnit\Framework\TestCase;

final class GetNamedStreetGeometryQueryHandlerTest extends TestCase
{
    private string $cityCode;
    private string $cityLabel;
    private string $roadBanId;
    private string $roadName;
    private string $direction;
    private string $fromHouseNumber;
    private string $toHouseNumber;
    private string $geometry;
    private $roadGeocoder;
    private $laneSectionMaker;

    public function setUp(): void
    {
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->laneSectionMaker = $this->createMock(LaneSectionMakerInterface::class);

        $this->cityCode = '44195';
        $this->cityLabel = 'Savenay';
        $this->roadName = 'Route du Grand Brossais';
        $this->roadBanId = '44195_0137';
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
            ->with($this->roadBanId)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                'fullLaneGeometry',
                $this->roadBanId,
                $this->roadName,
                $this->cityCode,
                $this->direction,
                null,
                $this->fromHouseNumber,
                null,
                null,
                $this->toHouseNumber,
                null,
            )
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
        $saveNamedStreetCommand->roadBanId = $this->roadBanId;
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
            ->method('getRoadBanId')
            ->willReturn('44195_1234'); // Changed
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
            ->with($this->roadBanId)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                'fullLaneGeometry',
                $this->roadBanId,
                $this->roadName,
                $this->cityCode,
                $this->direction,
                null,
                $this->fromHouseNumber,
                null,
                null,
                $this->toHouseNumber,
                null,
            )
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
        $saveNamedStreetCommand->roadBanId = $this->roadBanId;
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
            ->method('getRoadBanId')
            ->willReturn($this->roadBanId);
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
        $saveNamedStreetCommand->roadBanId = $this->roadBanId;
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
            ->with($this->roadBanId)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                'fullLaneGeometry',
                $this->roadBanId,
                $this->roadName,
                $this->cityCode,
                $this->direction,
                $fromCoords,
                null,
                null,
                $toCoords,
                null,
                null,
            )
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
        $saveNamedStreetCommand->roadBanId = $this->roadBanId;
        $saveNamedStreetCommand->roadName = $this->roadName;
        $saveNamedStreetCommand->fromCoords = $fromCoords;
        $saveNamedStreetCommand->toCoords = $toCoords;

        $result = $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand));

        $this->assertSame($this->geometry, $result);
    }

    public function testGetFullCity(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessage('not implemented: full city geocoding');

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
        $saveNamedStreetCommand->roadType = RoadTypeEnum::LANE->value;
        $saveNamedStreetCommand->direction = $this->direction;
        $saveNamedStreetCommand->cityCode = $this->cityCode;
        $saveNamedStreetCommand->cityLabel = $this->cityLabel;
        $saveNamedStreetCommand->roadBanId = null;
        $saveNamedStreetCommand->roadName = null;
        $saveNamedStreetCommand->fromCoords = null;
        $saveNamedStreetCommand->toCoords = null;

        $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand));
    }

    public function testComputeRoadBanIdWhenMissing(): void
    {
        $computedRoadBanId = '44195_9999';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadBanId')
            ->with($this->roadName, $this->cityCode)
            ->willReturn($computedRoadBanId);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with($computedRoadBanId)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                'fullLaneGeometry',
                $computedRoadBanId,
                $this->roadName,
                $this->cityCode,
                $this->direction,
                null,
                $this->fromHouseNumber,
                null,
                null,
                $this->toHouseNumber,
                null,
            )
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
        $saveNamedStreetCommand->roadBanId = null; // manquant
        $saveNamedStreetCommand->roadName = $this->roadName; // fourni
        $saveNamedStreetCommand->fromHouseNumber = $this->fromHouseNumber;
        $saveNamedStreetCommand->toHouseNumber = $this->toHouseNumber;

        $result = $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand));

        $this->assertSame($this->geometry, $result);
    }

    public function testComputeFromRoadBanIdWhenMissingButFromRoadNameProvided(): void
    {
        $computedFromRoadBanId = '44195_from_1234';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadBanId')
            ->with('Rue Du Départ', $this->cityCode)
            ->willReturn($computedFromRoadBanId);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with($this->roadBanId)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                'fullLaneGeometry',
                $this->roadBanId,
                $this->roadName,
                $this->cityCode,
                $this->direction,
                null,
                $this->fromHouseNumber,
                $computedFromRoadBanId,
                null,
                $this->toHouseNumber,
                null,
            )
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
        $saveNamedStreetCommand->roadBanId = $this->roadBanId; // déjà connu
        $saveNamedStreetCommand->roadName = $this->roadName;
        $saveNamedStreetCommand->fromHouseNumber = $this->fromHouseNumber;
        $saveNamedStreetCommand->toHouseNumber = $this->toHouseNumber;
        $saveNamedStreetCommand->fromRoadName = 'Rue Du Départ';
        $saveNamedStreetCommand->fromRoadBanId = null; // à calculer

        $result = $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand));

        $this->assertSame($this->geometry, $result);
    }

    public function testComputeToRoadBanIdWhenMissingButToRoadNameProvided(): void
    {
        $computedToRoadBanId = '44195_to_5678';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadBanId')
            ->with('Rue D\'Arrivée', $this->cityCode)
            ->willReturn($computedToRoadBanId);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with($this->roadBanId)
            ->willReturn('fullLaneGeometry');

        $this->laneSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->with(
                'fullLaneGeometry',
                $this->roadBanId,
                $this->roadName,
                $this->cityCode,
                $this->direction,
                null,
                $this->fromHouseNumber,
                null,
                null,
                $this->toHouseNumber,
                $computedToRoadBanId,
            )
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
        $saveNamedStreetCommand->roadBanId = $this->roadBanId; // déjà connu
        $saveNamedStreetCommand->roadName = $this->roadName;
        $saveNamedStreetCommand->fromHouseNumber = $this->fromHouseNumber;
        $saveNamedStreetCommand->toHouseNumber = $this->toHouseNumber;
        $saveNamedStreetCommand->toRoadName = 'Rue D\'Arrivée';
        $saveNamedStreetCommand->toRoadBanId = null; // à calculer

        $result = $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand));

        $this->assertSame($this->geometry, $result);
    }
}
