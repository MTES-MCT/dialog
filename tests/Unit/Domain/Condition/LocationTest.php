<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition;

use App\Domain\Condition\Location;
use App\Domain\Condition\RegulationCondition;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    public function testGetters(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);
        $location = new Location(
            'b4812143-c4d8-44e6-8c3a-34688becae6e',
            $regulationCondition,
            '44260',
            'Savenay',
            'Route du Grand Brossais',
            '15',
            'POINT(-1.935836 47.347024)',
            '37bis',
            'POINT(-1.930973 47.347917)',
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $location->getUuid());
        $this->assertSame($regulationCondition, $location->getRegulationCondition());
        $this->assertSame('44260', $location->getPostalCode());
        $this->assertSame('Savenay', $location->getCity());
        $this->assertSame('Route du Grand Brossais', $location->getRoadName());
        $this->assertSame('15', $location->getFromHouseNumber());
        $this->assertSame('POINT(-1.935836 47.347024)', $location->getFromPoint());
        $this->assertSame('37bis', $location->getToHouseNumber());
        $this->assertSame('POINT(-1.930973 47.347917)', $location->getToPoint());
    }

    public function testUpdate(): void
    {
        $regulationCondition = $this->createMock(RegulationCondition::class);

        $location = new Location(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $regulationCondition,
            '44260',
            'Savenay',
            'Route du Grand Brossais',
            '15',
            'POINT(-1.935836 47.347024)',
            '37bis',
            'POINT(-1.930973 47.347917)',
        );

        $newPostalCode = '44750';
        $newCity = 'Campbon';
        $newRoadName = 'La Forge Hervé';
        $newFromHouseNumber = '1';
        $newFromPoint = 'POINT(-1.938727 47.358454)';
        $newToHouseNumber = '4';
        $newToPoint = 'POINT(-1.940304 47.388473)';

        $location->update(
            $newPostalCode,
            $newCity,
            $newRoadName,
            $newFromHouseNumber,
            $newFromPoint,
            $newToHouseNumber,
            $newToPoint,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $location->getUuid());
        $this->assertSame($newPostalCode, $location->getPostalCode());
        $this->assertSame($newCity, $location->getCity());
        $this->assertSame($newRoadName, $location->getRoadName());
        $this->assertSame($newFromHouseNumber, $location->getFromHouseNumber());
        $this->assertSame($newFromPoint, $location->getFromPoint());
        $this->assertSame($newToHouseNumber, $location->getToHouseNumber());
        $this->assertSame($newToPoint, $location->getToPoint());
    }
}
