<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Measure;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class MeasureTest extends TestCase
{
    public function testMeasure(): void
    {
        $location = $this->createMock(Location::class);
        $period1 = $this->createMock(Period::class);
        $period2 = $this->createMock(Period::class);
        $period3 = $this->createMock(Period::class);

        $date = new \DateTime('2023-06-06');

        $measure = new Measure(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            location: $location,
            type: MeasureTypeEnum::NO_ENTRY->value,
            createdAt: $date,
            maxSpeed: null,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $measure->getUuid());
        $this->assertSame($location, $measure->getLocation());
        $this->assertSame(MeasureTypeEnum::NO_ENTRY->value, $measure->getType());
        $this->assertSame($date, $measure->getCreatedAt());
        $this->assertNull($measure->getMaxSpeed());
        $this->assertEmpty($measure->getPeriods()); // Automatically set by Doctrine

        $measure->update(MeasureTypeEnum::SPEED_LIMITATION->value, 50);
        $this->assertSame(MeasureTypeEnum::SPEED_LIMITATION->value, $measure->getType());
        $this->assertSame(50, $measure->getMaxSpeed());

        $measure->addPeriod($period1);
        $measure->addPeriod($period1); // Test duplicate
        $measure->addPeriod($period2);

        $this->assertEquals(new ArrayCollection([$period1, $period2]), $measure->getPeriods());

        $measure->removePeriod($period3); // Period that does not belong to the measure
        $measure->removePeriod($period2);

        $this->assertEquals(new ArrayCollection([$period1]), $measure->getPeriods());

        $location1 = $this->createMock(LocationNew::class);
        $location2 = $this->createMock(LocationNew::class);
        $location3 = $this->createMock(LocationNew::class);

        $measure->addLocationNew($location1);
        $measure->addLocationNew($location1); // Test duplicate
        $measure->addLocationNew($location2);
        $this->assertEquals(new ArrayCollection([$location1, $location2]), $measure->getLocationsNew());
        $this->assertEquals($location1, $measure->getLocationNew());

        $measure->removeLocationNew($location3); // Location that does not belong to the measure
        $measure->removeLocationNew($location2);
        $this->assertEquals(new ArrayCollection([$location1]), $measure->getLocationsNew());
    }
}
