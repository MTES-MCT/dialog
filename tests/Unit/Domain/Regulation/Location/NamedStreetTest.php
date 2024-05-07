<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Location;

use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use PHPUnit\Framework\TestCase;

final class NamedStreetTest extends TestCase
{
    public function testGetters(): void
    {
        $location = $this->createMock(Location::class);

        $namedstreet = new NamedStreet(
            uuid: 'b4812143-c4d8-44e6-8c3a-34688becae6e',
            location: $location,
            cityCode: '44195',
            cityLabel: 'Savenay',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            fromRoadName: null,
            toHouseNumber: '37bis',
            toRoadName: null,
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $namedstreet->getUuid());
        $this->assertSame($location, $namedstreet->getLocation());
        $this->assertSame('44195', $namedstreet->getCityCode());
        $this->assertSame('Savenay', $namedstreet->getCityLabel());
        $this->assertSame('Route du Grand Brossais', $namedstreet->getRoadName());
        $this->assertSame('15', $namedstreet->getFromHouseNumber());
        $this->assertSame(null, $namedstreet->getFromRoadName());
        $this->assertSame('37bis', $namedstreet->getToHouseNumber());
        $this->assertSame(null, $namedstreet->getToRoadName());

        $newCityCode = '44025';
        $newCityLabel = 'Campbon';
        $newRoadName = 'La Forge Hervé';
        $newFromHouseNumber = '1';
        $newToHouseNumber = '4';

        $namedstreet->update(
            $newCityCode,
            $newCityLabel,
            $newRoadName,
            $newFromHouseNumber,
            null,
            $newToHouseNumber,
            null,
        );

        $this->assertSame($newCityCode, $namedstreet->getCityCode());
        $this->assertSame($newCityLabel, $namedstreet->getCityLabel());
        $this->assertSame($newRoadName, $namedstreet->getRoadName());
        $this->assertSame($newFromHouseNumber, $namedstreet->getFromHouseNumber());
        $this->assertSame(null, $namedstreet->getFromRoadName());
        $this->assertSame($newToHouseNumber, $namedstreet->getToHouseNumber());
        $this->assertSame(null, $namedstreet->getToRoadName());
    }
}
