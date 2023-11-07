<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class PeriodTest extends TestCase
{
    public function testGetters(): void
    {
        $dailyRange = $this->createMock(DailyRange::class);
        $measure = $this->createMock(Measure::class);
        $start = new \DateTimeImmutable('2022-12-20 09:00:00');
        $end = new \DateTimeImmutable('2022-12-20 18:00:00');
        $period = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            $start,
            $end,
            PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
            $dailyRange,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $period->getUuid());
        $this->assertSame($start, $period->getStartDateTime());
        $this->assertSame($end, $period->getEndDateTime());
        $this->assertSame($measure, $period->getMeasure());
        $this->assertSame($dailyRange, $period->getDailyRange());
        $this->assertSame(PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value, $period->getRecurrenceType());

        $newStart = new \DateTimeImmutable('2022-12-20 09:00:00');
        $newEnd = new \DateTimeImmutable('2022-12-20 18:00:00');
        $period->update(
            $newStart,
            $newEnd,
            PeriodRecurrenceTypeEnum::EVERY_DAY->value,
        );

        $this->assertSame($newStart, $period->getStartDateTime());
        $this->assertSame($newEnd, $period->getEndDateTime());
        $this->assertSame(PeriodRecurrenceTypeEnum::EVERY_DAY->value, $period->getRecurrenceType());

        // Deprecated
        $period->setStartTime(null);
        $period->setEndTime(null);
        $period->setApplicableDays(null);
        $this->assertNull($period->getStartTime());
        $this->assertNull($period->getEndTime());
        $this->assertNull($period->getApplicableDays());
    }
}
