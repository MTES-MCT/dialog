<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Application\Regulation\Query\Location\GetZoneGeometryQuery;
use App\Application\Regulation\Query\Location\GetZoneGeometryQueryHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\Zone;
use PHPUnit\Framework\TestCase;

final class GetZoneGeometryQueryHandlerTest extends TestCase
{
    public function testCompute(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('findSectionsInArea')
            ->with('<polygon>', [RoadGeocoderInterface::HIGHWAY], true)
            ->willReturn('<sections>');

        $command = new SaveZoneCommand();
        $command->geometry = '<polygon>';

        $handler = new GetZoneGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<sections>', $handler(new GetZoneGeometryQuery($command)));
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

        $command = new SaveZoneCommand();
        $command->geometry = '<polygon>';

        $handler = new GetZoneGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<sections>', $handler(new GetZoneGeometryQuery($command, $location)));
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

        $command = new SaveZoneCommand();
        $command->geometry = '<polygon-new>';

        $handler = new GetZoneGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<sections-new>', $handler(new GetZoneGeometryQuery($command, $location)));
    }
}
