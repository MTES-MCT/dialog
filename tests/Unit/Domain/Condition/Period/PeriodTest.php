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
        $days = [
            ApplicableDayEnum::MONDAY->value,
            ApplicableDayEnum::TUESDAY->value,
            ApplicableDayEnum::WEDNESDAY->value,
            ApplicableDayEnum::FRIDAY->value,
        ];

        $period = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            true,
            $days,
            $start,
            $end,
        );

        $daysRange = [
            [
                'firstDay' => ApplicableDayEnum::MONDAY->value,
                'lastDay' => ApplicableDayEnum::WEDNESDAY->value,
            ],
            [
                'firstDay' => ApplicableDayEnum::FRIDAY->value,
                'lastDay' => ApplicableDayEnum::FRIDAY->value,
            ],
        ];

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $period->getUuid());
        $this->assertSame($days, $period->getApplicableDays());
        $this->assertSame($daysRange, $period->getDaysRanges());
        $this->assertSame($start, $period->getStartTime());
        $this->assertSame($end, $period->getEndTime());
        $this->assertSame($measure, $period->getMeasure());
        $this->assertTrue($period->isIncludeHolidays());

        $newStart = new \DateTimeImmutable('2022-12-20 09:00:00');
        $newEnd = new \DateTimeImmutable('2022-12-20 18:00:00');

        $updatedDaysRange = [
            [
                'firstDay' => ApplicableDayEnum::WEDNESDAY->value,
                'lastDay' => ApplicableDayEnum::WEDNESDAY->value,
            ],
        ];

        $period->update(false, [ApplicableDayEnum::WEDNESDAY->value], $newStart, $newEnd);
        $this->assertSame([ApplicableDayEnum::WEDNESDAY->value], $period->getApplicableDays());
        $this->assertSame($updatedDaysRange, $period->getDaysRanges());
        $this->assertSame($newStart, $period->getStartTime());
        $this->assertSame($newEnd, $period->getEndTime());
        $this->assertFalse($period->isIncludeHolidays());
    }
}
