<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    private Location $location;
    private Location $departmentalRoad;
    private string $geometry;
    private MockObject $measure;

    public function setUp(): void
    {
        $this->geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);
        $this->measure = $this->createMock(Measure::class);

        $this->location = new Location(
            uuid: 'b4812143-c4d8-44e6-8c3a-34688becae6e',
            measure: $this->measure,
            roadType: 'lane',
            cityCode: '44195',
            cityLabel: 'Savenay',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            toHouseNumber: '37bis',
            administrator: null,
            roadNumber: null,
            fromPointNumber: null,
            fromAbscissa: null,
            fromSide: null,
            toPointNumber: null,
            toAbscissa: null,
            toSide: null,
            geometry: $this->geometry,
        );

        $this->departmentalRoad = new Location(
            uuid: '8785a4c2-8f0d-423e-bd5b-641f228df23b',
            measure: $this->measure,
            roadType: 'departmentalRoad',
            cityCode: null,
            cityLabel: null,
            roadName: null,
            fromHouseNumber: null,
            toHouseNumber: null,
            administrator: 'Ardèche',
            roadNumber: 'D110',
            fromPointNumber: '14',
            fromAbscissa: 650,
            fromSide: 'U',
            toPointNumber: '16',
            toAbscissa: 250,
            toSide: 'U',
            geometry: 'sectionGeometry',
        );
    }

    public function testGetters(): void
    {
        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $this->location->getUuid());
        $this->assertSame($this->measure, $this->location->getMeasure());
        $this->assertSame('44195', $this->location->getCityCode());
        $this->assertSame('Savenay', $this->location->getCityLabel());
        $this->assertSame('Route du Grand Brossais', $this->location->getRoadName());
        $this->assertSame('15', $this->location->getFromHouseNumber());
        $this->assertSame('37bis', $this->location->getToHouseNumber());
        $this->assertSame($this->geometry, $this->location->getGeometry());

        $this->assertSame('Ardèche', $this->departmentalRoad->getAdministrator());
        $this->assertSame('D110', $this->departmentalRoad->getRoadNumber());
        $this->assertSame('14', $this->departmentalRoad->getFromPointNumber());
        $this->assertSame(650, $this->departmentalRoad->getFromAbscissa());
        $this->assertSame('U', $this->departmentalRoad->getFromSide());
        $this->assertSame('16', $this->departmentalRoad->getToPointNumber());
        $this->assertSame(250, $this->departmentalRoad->getToAbscissa());
        $this->assertSame('U', $this->departmentalRoad->getToSide());
    }

    public function testUpdate(): void
    {
        $newRoadType = 'lane';
        $newCityCode = '44025';
        $newCityLabel = 'Campbon';
        $newAdministrator = null;
        $newRoadNumber = null;
        $newRoadName = 'La Forge Hervé';
        $newFromHouseNumber = '1';
        $newToHouseNumber = '4';
        $newGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.938727, 47.358454),
            Coordinates::fromLonLat(-1.940304, 47.388473),
        ]);

        $this->location->update(
            $newRoadType,
            $newCityCode,
            $newCityLabel,
            $newRoadName,
            $newFromHouseNumber,
            $newToHouseNumber,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $newGeometry,
        );

        $this->departmentalRoad->update(
            'departmentalRoad',
            null,
            null,
            null,
            null,
            null,
            'Ain',
            'D16',
            '10',
            'D',
            0,
            '12',
            'D',
            0,
            $newGeometry,
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $this->location->getUuid());
        $this->assertSame($newRoadType, $this->location->getRoadType());
        $this->assertSame($newAdministrator, $this->location->getAdministrator());
        $this->assertSame($newRoadNumber, $this->location->getRoadNumber());
        $this->assertSame($newCityCode, $this->location->getCityCode());
        $this->assertSame($newCityLabel, $this->location->getCityLabel());
        $this->assertSame($newRoadName, $this->location->getRoadName());
        $this->assertSame($newFromHouseNumber, $this->location->getFromHouseNumber());
        $this->assertSame($newToHouseNumber, $this->location->getToHouseNumber());
        $this->assertSame($newGeometry, $this->location->getGeometry());

        $this->assertSame('Ain', $this->departmentalRoad->getAdministrator());
        $this->assertSame('D16', $this->departmentalRoad->getRoadNumber());
        $this->assertSame('10', $this->departmentalRoad->getFromPointNumber());
        $this->assertSame('D', $this->departmentalRoad->getFromSide());
        $this->assertSame(0, $this->departmentalRoad->getFromAbscissa());
        $this->assertSame('12', $this->departmentalRoad->getToPointNumber());
        $this->assertSame(0, $this->departmentalRoad->getToAbscissa());
        $this->assertSame('D', $this->departmentalRoad->getToSide());
    }
}
