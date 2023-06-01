<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    public function testGetters(): void
    {
        $measure1 = $this->createMock(Measure::class);
        $measure2 = $this->createMock(Measure::class);
        $measure3 = $this->createMock(Measure::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $location = new Location(
            'b4812143-c4d8-44e6-8c3a-34688becae6e',
            $regulationOrder,
            'Route du Grand Brossais 44260 Savenay',
            '15',
            'POINT(-1.935836 47.347024)',
            '37bis',
            'POINT(-1.930973 47.347917)',
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $location->getUuid());
        $this->assertSame($regulationOrder, $location->getRegulationOrder());
        $this->assertSame('Route du Grand Brossais 44260 Savenay', $location->getAddress());
        $this->assertSame('15', $location->getFromHouseNumber());
        $this->assertSame('POINT(-1.935836 47.347024)', $location->getFromPoint());
        $this->assertSame('37bis', $location->getToHouseNumber());
        $this->assertSame('POINT(-1.930973 47.347917)', $location->getToPoint());
        $this->assertEmpty($location->getMeasures());

        $location->addMeasure($measure1);
        $location->addMeasure($measure1); // Test doublon
        $location->addMeasure($measure2);

        $this->assertEquals(new ArrayCollection([$measure1, $measure2]), $location->getMeasures());

        $location->removeMeasure($measure3); // Measure that does not belong to the location
        $location->removeMeasure($measure2);

        $this->assertEquals(new ArrayCollection([$measure1]), $location->getMeasures());
    }

    public function testUpdate(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);

        $location = new Location(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $regulationOrder,
            'Route du Grand Brossais 44260 Savenay',
            '15',
            'POINT(-1.935836 47.347024)',
            '37bis',
            'POINT(-1.930973 47.347917)',
        );

        $newAddress = 'La Forge Hervé 44750 Campbon';
        $newFromHouseNumber = '1';
        $newFromPoint = 'POINT(-1.938727 47.358454)';
        $newToHouseNumber = '4';
        $newToPoint = 'POINT(-1.940304 47.388473)';

        $location->update(
            $newAddress,
            $newFromHouseNumber,
            $newFromPoint,
            $newToHouseNumber,
            $newToPoint,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $location->getUuid());
        $this->assertSame($newAddress, $location->getAddress());
        $this->assertSame($newFromHouseNumber, $location->getFromHouseNumber());
        $this->assertSame($newFromPoint, $location->getFromPoint());
        $this->assertSame($newToHouseNumber, $location->getToHouseNumber());
        $this->assertSame($newToPoint, $location->getToPoint());
    }
}
