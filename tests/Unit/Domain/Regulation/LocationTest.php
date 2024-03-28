<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    public function testGetters(): void
    {
        $geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);
        $referencePoints = [
            'pointA' => [
                'pointNumber' => 14,
                'abscissa' => 650,
            ],
            'pointB' => [
                'pointNumber' => 16,
                'abscissa' => 250,
            ],
            'direction' => 'U',
        ];

        $measure = $this->createMock(Measure::class);
        $location = new Location(
            uuid: 'b4812143-c4d8-44e6-8c3a-34688becae6e',
            measure: $measure,
            roadType: 'lane',
            cityCode: '44195',
            cityLabel: 'Savenay',
            administrator: null,
            roadNumber: null,
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            toHouseNumber: '37bis',
            geometry: $geometry,
            referencePoints: $referencePoints,
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $location->getUuid());
        $this->assertSame($measure, $location->getMeasure());
        $this->assertSame('44195', $location->getCityCode());
        $this->assertSame('Savenay', $location->getCityLabel());
        $this->assertSame('Route du Grand Brossais', $location->getRoadName());
        $this->assertSame('15', $location->getFromHouseNumber());
        $this->assertSame('37bis', $location->getToHouseNumber());
        $this->assertSame($geometry, $location->getGeometry());
        $this->assertSame('U', $location->getDirection());
        $this->assertSame(['pointNumber' => 14, 'abscissa' => 650], $location->getPointA());
        $this->assertSame(['pointNumber' => 16, 'abscissa' => 250], $location->getPointB());
    }

    public function testUpdate(): void
    {
        $measure = $this->createMock(Measure::class);

        $location = new Location(
            uuid: '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            measure: $measure,
            roadType: 'lane',
            cityCode: '44195',
            cityLabel: 'Savenay',
            administrator: null,
            roadNumber: null,
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            toHouseNumber: '37bis',
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            ]),
            referencePoints: null,
        );

        $newRoadType = 'lane';
        $newCityCode = '44025';
        $newCityLabel = 'Campbon';
        $newAdministrator = null;
        $newRoadNumber = null;
        $newRoadName = 'La Forge Hervé';
        $newFromHouseNumber = '1';
        $newToHouseNumber = '4';
        $newReferencePoints = [
            'pointA' => [
                'pointNumber' => 14,
                'abscissa' => 650,
            ],
            'pointB' => [
                'pointNumber' => 16,
                'abscissa' => 250,
            ],
            'direction' => 'U',
        ];
        $newGeometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.938727, 47.358454),
            Coordinates::fromLonLat(-1.940304, 47.388473),
        ]);

        $location->update(
            $newRoadType,
            $newAdministrator,
            $newRoadNumber,
            $newCityCode,
            $newCityLabel,
            $newRoadName,
            $newFromHouseNumber,
            $newToHouseNumber,
            $newGeometry,
            $newReferencePoints,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $location->getUuid());
        $this->assertSame($newRoadType, $location->getRoadType());
        $this->assertSame($newAdministrator, $location->getAdministrator());
        $this->assertSame($newRoadNumber, $location->getRoadNumber());
        $this->assertSame($newCityCode, $location->getCityCode());
        $this->assertSame($newCityLabel, $location->getCityLabel());
        $this->assertSame($newRoadName, $location->getRoadName());
        $this->assertSame($newFromHouseNumber, $location->getFromHouseNumber());
        $this->assertSame($newToHouseNumber, $location->getToHouseNumber());
        $this->assertSame($newGeometry, $location->getGeometry());
        $this->assertSame('U', $location->getDirection());
        $this->assertSame(['pointNumber' => 14, 'abscissa' => 650], $location->getPointA());
        $this->assertSame(['pointNumber' => 16, 'abscissa' => 250], $location->getPointB());
    }
}
