<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\Exception\EmptyRoadBanIdException;
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
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @dataProvider emptyRoadBanIdDataProvider
     */
    public function testThrowEmptyRoadBanIdExceptionWhenRoadBanIdEmpty(
        ?string $fromRoadName,
        ?string $toRoadName,
        ?string $roadBanId,
        ?string $fromRoadBanId,
        ?string $toRoadBanId,
    ): void {
        $this->expectException(EmptyRoadBanIdException::class);

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
        $saveNamedStreetCommand->roadName = $this->roadName;
        $saveNamedStreetCommand->fromRoadName = $fromRoadName;
        $saveNamedStreetCommand->toRoadName = $toRoadName;
        $saveNamedStreetCommand->roadBanId = $roadBanId;
        $saveNamedStreetCommand->fromRoadBanId = $fromRoadBanId;
        $saveNamedStreetCommand->toRoadBanId = $toRoadBanId;

        $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand));
    }

    public static function emptyRoadBanIdDataProvider(): iterable
    {
        return [
            'roadBanId empty' => ['Route du Mia', 'Impasse des Sapins', null, '62108_0100', '62108_0100'],
            'fromRoadBanId empty' => ['Route du Mia', 'Impasse des Sapins', '44195_0137', null, '62108_0100'],
            'toRoadBanId empty' => ['Route du Mia', 'Impasse des Sapins', '44195_0137', '62108_0100', null],
        ];
    }

    /**
     * @dataProvider roadBanIdDataProvider
     */
    public function testRoadBanIsFilled(
        ?string $fromRoadName,
        ?string $toRoadName,
        ?string $roadBanId,
        ?string $fromRoadBanId,
        ?string $toRoadBanId,
    ): void {
        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine');

        $this->laneSectionMaker
            ->expects(self::once())
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
        $saveNamedStreetCommand->roadName = $this->roadName;
        $saveNamedStreetCommand->fromRoadName = $fromRoadName;
        $saveNamedStreetCommand->toRoadName = $toRoadName;
        $saveNamedStreetCommand->roadBanId = $roadBanId;
        $saveNamedStreetCommand->fromRoadBanId = $fromRoadBanId;
        $saveNamedStreetCommand->toRoadBanId = $toRoadBanId;

        $handler(new GetNamedStreetGeometryQuery($saveNamedStreetCommand));
    }

    public static function roadBanIdDataProvider(): iterable
    {
        return [
            'full filled' => ['Route du Mia', 'Impasse des Sapins', '44195_0137', '62108_0100', '62108_0100'],
            'fromRoadName empty' => [null, 'Impasse des Sapins', '44195_0137', '62108_0100', '62108_0100'],
            'toRoadName empty' => ['Route du Mia', null, '44195_0137', '62108_0100', '62108_0100'],
        ];
    }
}
