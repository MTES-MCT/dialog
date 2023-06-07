<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class PeriodTest extends TestCase
{
    public function testGetters(): void
    {
        $measure = $this->createMock(Measure::class);
        $start = new \DateTimeImmutable('2022-12-20 09:00:00');
        $end = new \DateTimeImmutable('2022-12-20 18:00:00');
        $period = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            true,
            [ApplicableDayEnum::MONDAY, ApplicableDayEnum::THURSDAY],
            $start,
            $end,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $period->getUuid());
        $this->assertSame([ApplicableDayEnum::MONDAY, ApplicableDayEnum::THURSDAY], $period->getApplicableDays());
        $this->assertSame($start, $period->getStartTime());
        $this->assertSame($end, $period->getEndTime());
        $this->assertSame($measure, $period->getMeasure());
        $this->assertTrue($period->isIncludeHolidays());

        $newStart = new \DateTimeImmutable('2022-12-20 09:00:00');
        $newEnd = new \DateTimeImmutable('2022-12-20 18:00:00');

        $period->update(false, [ApplicableDayEnum::WEDNESDAY], $newStart, $newEnd);
        $this->assertSame([ApplicableDayEnum::WEDNESDAY], $period->getApplicableDays());
        $this->assertSame($newStart, $period->getStartTime());
        $this->assertSame($newEnd, $period->getEndTime());
        $this->assertFalse($period->isIncludeHolidays());
    }
}
