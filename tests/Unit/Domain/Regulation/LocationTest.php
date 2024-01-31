<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    public function testGetters(): void
    {
        $geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $measure1 = $this->createMock(Measure::class);
        $measure2 = $this->createMock(Measure::class);
        $measure3 = $this->createMock(Measure::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $location = new Location(
            'b4812143-c4d8-44e6-8c3a-34688becae6e',
            $regulationOrder,
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '44195',
            cityLabel: 'Savenay',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            toHouseNumber: '37bis',
            geometry: $geometry,
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $location->getUuid());
        $this->assertSame($regulationOrder, $location->getRegulationOrder());
        $this->assertSame('lane', $location->getRoadType());
        $this->assertSame(null, $location->getAdministrator());
        $this->assertSame(null, $location->getRoadNumber());
        $this->assertSame('44195', $location->getCityCode());
        $this->assertSame('Savenay', $location->getCityLabel());
        $this->assertSame('Route du Grand Brossais', $location->getRoadNAme());
        $this->assertSame('15', $location->getFromHouseNumber());
        $this->assertSame('37bis', $location->getToHouseNumber());
        $this->assertSame($geometry, $location->getGeometry());
        $this->assertEmpty($location->getMeasures());

        $location->addMeasure($measure1);
        $location->addMeasure($measure1); // Test doublon
        $location->addMeasure($measure2);

        $this->assertEquals(new ArrayCollection([$measure1, $measure2]), $location->getMeasures());

        $location->removeMeasure($measure3); // Measure that does not belong to the location
        $location->removeMeasure($measure2);

        $this->assertEquals(new ArrayCollection([$measure1]), $location->getMeasures());

        // Deprecated
        $this->assertNull($location->getFromPoint());
        $this->assertNull($location->getToPoint());
        $this->assertNull($location->getAddress());
    }

    public function testUpdate(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);

        $location = new Location(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $regulationOrder,
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
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

        $newRoadType = 'lane';
        $newAdministrator = null;
        $newRoadNumber = null;
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
            $newRoadType,
            $newAdministrator,
            $newRoadNumber,
            $newCityCode,
            $newCityLabel,
            $newRoadName,
            $newFromHouseNumber,
            $newToHouseNumber,
            $newGeometry,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $location->getUuid());
        $this->assertSame($newRoadType, $location->getRoadType());
        $this->assertSame($newAdministrator, $location->getAdministrator());
        $this->assertSame($newRoadNumber, $location->getRoadNumber());
        $this->assertSame($newCityCode, $location->getCityCode());
        $this->assertSame($newCityLabel, $location->getCityLabel());
        $this->assertSame($newRoadName, $location->getRoadName());
        $this->assertSame($newFromHouseNumber, $location->getFromHouseNumber());
        $this->assertSame($newToHouseNumber, $location->getToHouseNumber());
        $this->assertSame($newGeometry, $location->getGeometry());
    }
}
