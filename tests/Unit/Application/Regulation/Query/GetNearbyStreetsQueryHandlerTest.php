<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetNearbyStreetsQuery;
use App\Application\Regulation\Query\GetNearbyStreetsQueryHandler;
use App\Application\RoadGeocoderInterface;
use PHPUnit\Framework\TestCase;

final class GetNearbyStreetsQueryHandlerTest extends TestCase
{
    public function testGetNearbyStreets(): void
    {
        $geometry = '{"type":"Point","coordinates":[2.35,48.85]}';
        $expectedStreets = [
            ['roadName' => 'Rue de Rivoli', 'distance' => 12.3],
            ['roadName' => 'Rue Saint-Honoré', 'distance' => 45.7],
        ];

        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('findNearbyStreets')
            ->with($geometry, 150, 5)
            ->willReturn($expectedStreets);

        $handler = new GetNearbyStreetsQueryHandler($roadGeocoder);
        $result = $handler(new GetNearbyStreetsQuery(
            geometry: $geometry,
            radius: 150,
            limit: 5,
        ));

        $this->assertSame($expectedStreets, $result);
    }

    public function testGetNearbyStreetsEmpty(): void
    {
        $geometry = '{"type":"Point","coordinates":[0,0]}';

        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('findNearbyStreets')
            ->with($geometry, 100, 10)
            ->willReturn([]);

        $handler = new GetNearbyStreetsQueryHandler($roadGeocoder);
        $result = $handler(new GetNearbyStreetsQuery(
            geometry: $geometry,
            radius: 100,
            limit: 10,
        ));

        $this->assertSame([], $result);
    }
}
