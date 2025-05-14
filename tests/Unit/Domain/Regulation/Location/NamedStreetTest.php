<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Location;

use App\Domain\Regulation\Enum\DirectionEnum;
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
            direction: DirectionEnum::BOTH->value,
            cityCode: '44195',
            cityLabel: 'Savenay',
            roadBanId: '44195_0137',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            fromRoadBanId: null,
            fromRoadName: null,
            toHouseNumber: '37bis',
            toRoadBanId: null,
            toRoadName: null,
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $namedstreet->getUuid());
        $this->assertSame($location, $namedstreet->getLocation());
        $this->assertSame('44195', $namedstreet->getCityCode());
        $this->assertSame('Savenay', $namedstreet->getCityLabel());
        $this->assertSame('Route du Grand Brossais', $namedstreet->getRoadName());
        $this->assertSame('44195_0137', $namedstreet->getRoadBanId());
        $this->assertSame('15', $namedstreet->getFromHouseNumber());
        $this->assertSame(null, $namedstreet->getFromRoadBanId());
        $this->assertSame(null, $namedstreet->getFromRoadName());
        $this->assertSame('37bis', $namedstreet->getToHouseNumber());
        $this->assertSame(null, $namedstreet->getToRoadBanId());
        $this->assertSame(null, $namedstreet->getToRoadName());
        $this->assertSame(DirectionEnum::BOTH->value, $namedstreet->getDirection());

        $newCityCode = '44025';
        $newCityLabel = 'Campbon';
        $newRoadBanId = '44025_B136';
        $newRoadName = 'La Forge Hervé';
        $newFromHouseNumber = '1';
        $newToHouseNumber = '4';
        $newDirection = DirectionEnum::A_TO_B->value;

        $namedstreet->update(
            $newDirection,
            $newCityCode,
            $newCityLabel,
            $newRoadBanId,
            $newRoadName,
            $newFromHouseNumber,
            null,
            null,
            $newToHouseNumber,
            null,
            null,
        );

        $this->assertSame($newDirection, $namedstreet->getDirection());
        $this->assertSame($newCityCode, $namedstreet->getCityCode());
        $this->assertSame($newCityLabel, $namedstreet->getCityLabel());
        $this->assertSame($newRoadBanId, $namedstreet->getRoadBanId());
        $this->assertSame($newRoadName, $namedstreet->getRoadName());
        $this->assertSame($newFromHouseNumber, $namedstreet->getFromHouseNumber());
        $this->assertSame(null, $namedstreet->getFromRoadBanId());
        $this->assertSame(null, $namedstreet->getFromRoadName());
        $this->assertSame($newToHouseNumber, $namedstreet->getToHouseNumber());
        $this->assertSame(null, $namedstreet->getToRoadBanId());
        $this->assertSame(null, $namedstreet->getToRoadName());
    }
}
