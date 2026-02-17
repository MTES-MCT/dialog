<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Adapter\APIAdresseGeocoder;
use App\Infrastructure\Adapter\BdTopoRoadGeocoder;
use App\Infrastructure\Adapter\BdTopoStreetGeocoderAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BdTopoStreetGeocoderAdapterTest extends TestCase
{
    private APIAdresseGeocoder&MockObject $apiAdresseGeocoder;

    private BdTopoRoadGeocoder&MockObject $bdTopoRoadGeocoder;

    private BdTopoStreetGeocoderAdapter $adapter;

    protected function setUp(): void
    {
        $this->apiAdresseGeocoder = $this->createMock(APIAdresseGeocoder::class);
        $this->bdTopoRoadGeocoder = $this->createMock(BdTopoRoadGeocoder::class);
        $this->adapter = new BdTopoStreetGeocoderAdapter($this->apiAdresseGeocoder, $this->bdTopoRoadGeocoder);
    }

    public function testFindNamedStreetsDelegatesToBdTopo(): void
    {
        $expected = [
            ['roadBanId' => '93070_3185', 'roadName' => 'Rue Eugène Berthoud'],
        ];
        $this->bdTopoRoadGeocoder->expects($this->once())
            ->method('findNamedStreets')
            ->with('Rue Eugène', '93070')
            ->willReturn($expected);

        $this->apiAdresseGeocoder->expects($this->never())->method('findNamedStreets');

        $this->assertSame($expected, $this->adapter->findNamedStreets('Rue Eugène', '93070'));
    }

    public function testGetRoadBanIdDelegatesToBdTopoAndReturnsFirst(): void
    {
        $this->bdTopoRoadGeocoder->expects($this->once())
            ->method('findNamedStreets')
            ->with('Recolet', '59606')
            ->willReturn([
                ['roadBanId' => '59606_3210', 'roadName' => 'Rue des Récollets'],
            ]);

        $this->assertSame('59606_3210', $this->adapter->getRoadBanId('Recolet', '59606'));
    }

    public function testGetRoadBanIdThrowsWhenNoResult(): void
    {
        $this->bdTopoRoadGeocoder->expects($this->once())
            ->method('findNamedStreets')
            ->with('Inconnu', '59606')
            ->willReturn([]);

        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches('/no named street found/');

        $this->adapter->getRoadBanId('Inconnu', '59606');
    }

    public function testFindCitiesDelegatesToApiAdresse(): void
    {
        $expected = [
            ['label' => 'Dijon (21000)', 'code' => '21231'],
        ];
        $this->apiAdresseGeocoder->expects($this->once())
            ->method('findCities')
            ->with('Dijon')
            ->willReturn($expected);

        $this->bdTopoRoadGeocoder->expects($this->never())->method('findNamedStreets');

        $this->assertSame($expected, $this->adapter->findCities('Dijon'));
    }

    public function testComputeCoordinatesDelegatesToApiAdresse(): void
    {
        $coords = Coordinates::fromLonLat(0.5, 44.3);
        $this->apiAdresseGeocoder->expects($this->once())
            ->method('computeCoordinates')
            ->with('15 Route du Grand Brossais', '44195')
            ->willReturn($coords);

        $this->assertSame($coords, $this->adapter->computeCoordinates('15 Route du Grand Brossais', '44195'));
    }
}
