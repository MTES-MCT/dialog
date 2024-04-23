<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Location;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    public function testGetters(): void
    {
        $namedStreet = $this->createMock(NamedStreet::class);
        $namedStreet2 = $this->createMock(NamedStreet::class);
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad2 = $this->createMock(NumberedRoad::class);
        $measure = $this->createMock(Measure::class);
        $geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $namedStreetLocation = new Location(
            uuid: '3c549b5c-3c36-4a4d-a0a7-2bbfacc36736',
            measure: $measure,
            roadType: 'lane',
            geometry: $geometry,
            namedStreet: $namedStreet,
        );

        $numberedRoadLocation = new Location(
            uuid: 'a6b8f7db-2901-4588-b05c-fac633481d1e',
            measure: $measure,
            roadType: 'departmentalRoad',
            geometry: $geometry,
            numberedRoad: $numberedRoad,
        );

        $this->assertSame('3c549b5c-3c36-4a4d-a0a7-2bbfacc36736', $namedStreetLocation->getUuid());
        $this->assertSame($measure, $namedStreetLocation->getMeasure());
        $this->assertSame($geometry, $namedStreetLocation->getGeometry());
        $this->assertSame('lane', $namedStreetLocation->getRoadType());

        $this->assertSame('a6b8f7db-2901-4588-b05c-fac633481d1e', $numberedRoadLocation->getUuid());
        $this->assertSame($measure, $numberedRoadLocation->getMeasure());
        $this->assertSame($geometry, $numberedRoadLocation->getGeometry());
        $this->assertSame('departmentalRoad', $numberedRoadLocation->getRoadType());

        $this->assertSame($numberedRoad, $numberedRoadLocation->getNumberedRoad());
        $this->assertSame($namedStreet, $namedStreetLocation->getNamedStreet());

        $numberedRoadLocation->setNumberedRoad($numberedRoad2);
        $namedStreetLocation->setNamedStreet($namedStreet2);

        $this->assertSame($numberedRoad2, $numberedRoadLocation->getNumberedRoad());
        $this->assertSame($namedStreet2, $namedStreetLocation->getNamedStreet());
    }
}
