<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Application\Regulation\Query\Location\GetRawGeoJSONGeometryQuery;
use App\Application\Regulation\Query\Location\GetZoneGeometryQuery;
use App\Application\Regulation\Query\Location\GetZoneGeometryQueryHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\WholeCityException;
use App\Domain\Regulation\Location\Zone;
use PHPUnit\Framework\TestCase;

final class GetZoneGeometryQueryHandlerTest extends TestCase
{
    private $queryBus;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
    }

    private function makeRawGeoJSONException(string $label, string $geometry): SaveWholeCityExceptionCommand
    {
        $exception = new SaveWholeCityExceptionCommand();
        $exception->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $exception->namedStreet = null;
        $exception->rawGeoJSON = new SaveRawGeoJSONCommand();
        $exception->rawGeoJSON->label = $label;
        $exception->rawGeoJSON->geometry = $geometry;

        return $exception;
    }

    public function testCompute(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('findSectionsInArea')
            ->with('<polygon>', [RoadGeocoderInterface::HIGHWAY], true)
            ->willReturn('<sections>');
        $roadGeocoder
            ->expects(self::never())
            ->method('subtractGeometries');

        $command = new SaveZoneCommand();
        $command->geometry = '<polygon>';

        $handler = new GetZoneGeometryQueryHandler($roadGeocoder, $this->queryBus);

        $this->assertSame('<sections>', $handler(new GetZoneGeometryQuery($command)));
    }

    public function testComputeSubtractsExceptions(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('findSectionsInArea')
            ->with('<polygon>', [RoadGeocoderInterface::HIGHWAY], true)
            ->willReturn('<sections>');

        $exception = $this->makeRawGeoJSONException('Rue exclue', '<exception>');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetRawGeoJSONGeometryQuery($exception->rawGeoJSON))
            ->willReturn('<exception-geometry>');

        $roadGeocoder
            ->expects(self::once())
            ->method('subtractGeometries')
            ->with('<sections>', ['<exception-geometry>'])
            ->willReturn('<sections-minus-exceptions>');

        $command = new SaveZoneCommand();
        $command->geometry = '<polygon>';
        $command->exceptions = [$exception];

        $handler = new GetZoneGeometryQueryHandler($roadGeocoder, $this->queryBus);

        $this->assertSame('<sections-minus-exceptions>', $handler(new GetZoneGeometryQuery($command)));
    }

    public function testReturnsCachedLocationGeometryWhenPolygonUnchanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder->expects(self::never())->method('findSectionsInArea');

        $zone = $this->createMock(Zone::class);
        $zone->method('getGeometry')->willReturn('<polygon>');

        $location = $this->createMock(Location::class);
        $location->method('getZone')->willReturn($zone);
        $location->method('getGeometry')->willReturn('<sections>');
        $location->method('getExceptions')->willReturn([]);

        $command = new SaveZoneCommand();
        $command->geometry = '<polygon>';

        $handler = new GetZoneGeometryQueryHandler($roadGeocoder, $this->queryBus);

        $this->assertSame('<sections>', $handler(new GetZoneGeometryQuery($command, $location)));
    }

    public function testReturnsCachedLocationGeometryWhenPolygonAndExceptionsUnchanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder->expects(self::never())->method('findSectionsInArea');
        $roadGeocoder->expects(self::never())->method('subtractGeometries');

        $exception = $this->makeRawGeoJSONException('Rue exclue', '<exception>');

        $zone = $this->createMock(Zone::class);
        $zone->method('getGeometry')->willReturn('<polygon>');

        $location = $this->createMock(Location::class);
        $location->method('getZone')->willReturn($zone);
        $location->method('getGeometry')->willReturn('<sections-minus-exceptions>');
        $location->method('getExceptions')->willReturn([
            new WholeCityException(
                uuid: '0662d842-0d55-7ff1-8000-3ba613adca43',
                location: $location,
                roadType: RoadTypeEnum::RAW_GEOJSON->value,
                label: 'Rue exclue',
                geometry: '<exception-geometry>',
                data: $exception->toData(),
            ),
        ]);

        $command = new SaveZoneCommand();
        $command->geometry = '<polygon>';
        $command->exceptions = [$exception];

        $handler = new GetZoneGeometryQueryHandler($roadGeocoder, $this->queryBus);

        $this->assertSame('<sections-minus-exceptions>', $handler(new GetZoneGeometryQuery($command, $location)));
    }

    public function testRecomputesWhenPolygonChanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('findSectionsInArea')
            ->with('<polygon-new>', [RoadGeocoderInterface::HIGHWAY], true)
            ->willReturn('<sections-new>');

        $zone = $this->createMock(Zone::class);
        $zone->method('getGeometry')->willReturn('<polygon-old>');

        $location = $this->createMock(Location::class);
        $location->method('getZone')->willReturn($zone);
        $location->method('getGeometry')->willReturn('<sections>');
        $location->method('getExceptions')->willReturn([]);

        $command = new SaveZoneCommand();
        $command->geometry = '<polygon-new>';

        $handler = new GetZoneGeometryQueryHandler($roadGeocoder, $this->queryBus);

        $this->assertSame('<sections-new>', $handler(new GetZoneGeometryQuery($command, $location)));
    }

    public function testRecomputesWhenExceptionsChanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('findSectionsInArea')
            ->with('<polygon>', [RoadGeocoderInterface::HIGHWAY], true)
            ->willReturn('<sections>');

        $exception = $this->makeRawGeoJSONException('Rue exclue', '<exception>');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->willReturn('<exception-geometry>');

        $roadGeocoder
            ->expects(self::once())
            ->method('subtractGeometries')
            ->with('<sections>', ['<exception-geometry>'])
            ->willReturn('<sections-minus-exceptions>');

        $zone = $this->createMock(Zone::class);
        $zone->method('getGeometry')->willReturn('<polygon>');

        $location = $this->createMock(Location::class);
        $location->method('getZone')->willReturn($zone);
        $location->method('getGeometry')->willReturn('<sections>');
        $location->method('getExceptions')->willReturn([]);

        $command = new SaveZoneCommand();
        $command->geometry = '<polygon>';
        $command->exceptions = [$exception];

        $handler = new GetZoneGeometryQueryHandler($roadGeocoder, $this->queryBus);

        $this->assertSame('<sections-minus-exceptions>', $handler(new GetZoneGeometryQuery($command, $location)));
    }
}
