<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Location;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Location\StorageArea;
use App\Domain\Regulation\Location\WholeCityException;
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
        $rawGeoJSON = $this->createMock(RawGeoJSON::class);
        $rawGeoJSON2 = $this->createMock(RawGeoJSON::class);
        $measure = $this->createMock(Measure::class);
        $storageArea = $this->createMock(StorageArea::class);
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

        $rawGeoJSONLocation = new Location(
            uuid: 'c4bc0255-3546-4e04-bf30-f9c8699778ad',
            measure: $measure,
            roadType: 'rawGeoJSON',
            geometry: $geometry,
            rawGeoJSON: $rawGeoJSON,
        );

        $this->assertSame('3c549b5c-3c36-4a4d-a0a7-2bbfacc36736', $namedStreetLocation->getUuid());
        $this->assertSame($measure, $namedStreetLocation->getMeasure());
        $this->assertSame($geometry, $namedStreetLocation->getGeometry());
        $this->assertSame('lane', $namedStreetLocation->getRoadType());

        $this->assertSame('a6b8f7db-2901-4588-b05c-fac633481d1e', $numberedRoadLocation->getUuid());
        $this->assertSame($measure, $numberedRoadLocation->getMeasure());
        $this->assertSame($geometry, $numberedRoadLocation->getGeometry());
        $this->assertSame('departmentalRoad', $numberedRoadLocation->getRoadType());

        $this->assertNull($numberedRoadLocation->getStorageArea()); // Automatically set by Doctrine
        $numberedRoadLocation->setStorageArea($storageArea);
        $this->assertSame($storageArea, $numberedRoadLocation->getStorageArea());
        $numberedRoadLocation->setStorageArea(null);
        $this->assertNull($numberedRoadLocation->getStorageArea());

        $this->assertSame('c4bc0255-3546-4e04-bf30-f9c8699778ad', $rawGeoJSONLocation->getUuid());
        $this->assertSame($measure, $rawGeoJSONLocation->getMeasure());
        $this->assertSame($geometry, $rawGeoJSONLocation->getGeometry());
        $this->assertSame('rawGeoJSON', $rawGeoJSONLocation->getRoadType());

        $this->assertSame($numberedRoad, $numberedRoadLocation->getNumberedRoad());
        $this->assertSame($namedStreet, $namedStreetLocation->getNamedStreet());

        $namedStreet
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn('road name');
        $this->assertSame('road name', $namedStreetLocation->getCifsStreetLabel());

        $numberedRoad
            ->expects(self::once())
            ->method('getRoadNumber')
            ->willReturn('road number');
        $this->assertSame('road number', $numberedRoadLocation->getCifsStreetLabel());

        $rawGeoJSON
            ->expects(self::once())
            ->method('getLabel')
            ->willReturn('label');
        $this->assertSame('label', $rawGeoJSONLocation->getCifsStreetLabel());

        $numberedRoadLocation->setNumberedRoad($numberedRoad2);
        $namedStreetLocation->setNamedStreet($namedStreet2);
        $rawGeoJSONLocation->setRawGeoJSON($rawGeoJSON2);

        $this->assertSame($numberedRoad2, $numberedRoadLocation->getNumberedRoad());
        $this->assertSame($namedStreet2, $namedStreetLocation->getNamedStreet());
        $this->assertSame($rawGeoJSON2, $rawGeoJSONLocation->getRawGeoJSON());
    }

    public function testWholeCity(): void
    {
        $location = new Location(
            uuid: 'b3c3c8a2-0000-0000-0000-000000000000',
            measure: $this->createMock(Measure::class),
            roadType: 'wholeCity',
            geometry: null,
            cityCode: '59350',
            cityLabel: 'Lille',
        );

        $this->assertSame('59350', $location->getCityCode());
        $this->assertSame('Lille', $location->getCityLabel());
        $this->assertSame('Lille', $location->getCifsStreetLabel());
        $this->assertSame([], $location->getExceptions());

        // setWholeCity met à jour les champs
        $location->setWholeCity('59009', 'Avesnelles');
        $this->assertSame('59009', $location->getCityCode());
        $this->assertSame('Avesnelles', $location->getCityLabel());

        // exceptions : ajout / lecture (réindexée) / suppression
        $exception = $this->createMock(WholeCityException::class);
        $location->addException($exception);
        $this->assertSame([$exception], $location->getExceptions());
        $location->addException($exception); // pas de doublon
        $this->assertCount(1, $location->getExceptions());
        $location->removeException($exception);
        $this->assertSame([], $location->getExceptions());

        // update() vers un autre type remet à null les champs ville
        $location->setWholeCity('59350', 'Lille');
        $location->update('lane', '<geom>');
        $this->assertNull($location->getCityCode());
        $this->assertNull($location->getCityLabel());

        // update() en wholeCity préserve les champs ville
        $location->setWholeCity('59350', 'Lille');
        $location->update('wholeCity', '<geom>');
        $this->assertSame('59350', $location->getCityCode());
        $this->assertSame('Lille', $location->getCityLabel());
    }
}
