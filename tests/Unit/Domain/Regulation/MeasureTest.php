<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class MeasureTest extends TestCase
{
    public function testMeasure(): void
    {
        $location = $this->createMock(Location::class);

        $measure = new Measure(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            location: $location,
            type: MeasureTypeEnum::NO_ENTRY->value,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $measure->getUuid());
        $this->assertSame($location, $measure->getLocation());
        $this->assertSame(MeasureTypeEnum::NO_ENTRY->value, $measure->getType());
        $this->assertEmpty($measure->getConditions()); // Automatically set by Doctrine

        $measure->update(MeasureTypeEnum::ALTERNATE_ROAD->value);
        $this->assertSame(MeasureTypeEnum::ALTERNATE_ROAD->value, $measure->getType());
    }
}
