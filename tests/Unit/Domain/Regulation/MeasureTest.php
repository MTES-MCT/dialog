<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location;
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
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $measure->getUuid());
        $this->assertSame($location, $measure->getLocation());
        $this->assertSame(MeasureTypeEnum::NO_ENTRY->value, $measure->getType());
        $this->assertSame($date, $measure->getCreatedAt());
        $this->assertEmpty($measure->getPeriods()); // Automatically set by Doctrine

        $measure->update(MeasureTypeEnum::ALTERNATE_ROAD->value);
        $this->assertSame(MeasureTypeEnum::ALTERNATE_ROAD->value, $measure->getType());

        $measure->addPeriod($period1);
        $measure->addPeriod($period1); // Test duplicate
        $measure->addPeriod($period2);

        $this->assertEquals(new ArrayCollection([$period1, $period2]), $measure->getPeriods());

        $measure->removePeriod($period3); // Period that does not belong to the measure
        $measure->removePeriod($period2);

        $this->assertEquals(new ArrayCollection([$period1]), $measure->getPeriods());
    }
}
