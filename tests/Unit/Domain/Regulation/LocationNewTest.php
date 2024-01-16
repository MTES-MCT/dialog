<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class LocationNewTest extends TestCase
{
    public function testGetters(): void
    {
        $geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $measure = $this->createMock(Measure::class);
        $location = new LocationNew(
            uuid: 'b4812143-c4d8-44e6-8c3a-34688becae6e',
            measure: $measure,
            cityCode: '44195',
            cityLabel: 'Savenay',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            toHouseNumber: '37bis',
            geometry: $geometry,
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $location->getUuid());
        $this->assertSame($measure, $location->getMeasure());
        $this->assertSame('44195', $location->getCityCode());
        $this->assertSame('Savenay', $location->getCityLabel());
        $this->assertSame('Route du Grand Brossais', $location->getRoadName());
        $this->assertSame('15', $location->getFromHouseNumber());
        $this->assertSame('37bis', $location->getToHouseNumber());
        $this->assertSame($geometry, $location->getGeometry());
    }

    public function testUpdate(): void
    {
        $measure = $this->createMock(Measure::class);

        $location = new LocationNew(
            uuid: '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            measure: $measure,
            cityCode: '44195',
            cityLabel: 'Savenay',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            toHouseNumber: '37bis',
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            ]),
        );

        $newCityCode = '44025';
        $newCityLabel = 'Campbon';
        $newRoadName = 'La Forge Hervé';
        $newFromHouseNumber = '1';
        $newToHouseNumber = '4';
        $newGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.938727, 47.358454),
            Coordinates::fromLonLat(-1.940304, 47.388473),
        ]);

        $location->update(
            $newCityCode,
            $newCityLabel,
            $newRoadName,
            $newFromHouseNumber,
            $newToHouseNumber,
            $newGeometry,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $location->getUuid());
        $this->assertSame($newCityCode, $location->getCityCode());
        $this->assertSame($newCityLabel, $location->getCityLabel());
        $this->assertSame($newRoadName, $location->getRoadName());
        $this->assertSame($newFromHouseNumber, $location->getFromHouseNumber());
        $this->assertSame($newToHouseNumber, $location->getToHouseNumber());
        $this->assertSame($newGeometry, $location->getGeometry());
    }
}