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

    public function testDaysRanges(): void
    {
        $measure = $this->createMock(Measure::class);
        $start = new \DateTimeImmutable('2022-12-20 09:00:00');
        $end = new \DateTimeImmutable('2022-12-20 18:00:00');

        // One day
        $oneDay = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            true,
            [ApplicableDayEnum::MONDAY->value],
            $start,
            $end,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::MONDAY->value],
        ], $oneDay->getDaysRanges());

        // Two consecutives days
        $twoConsecutiveDays = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            true,
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value],
            $start,
            $end,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::TUESDAY->value],
        ], $twoConsecutiveDays->getDaysRanges());

        // Three consecutive days
        $threeConsecutiveDays = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            true,
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::WEDNESDAY->value],
            $start,
            $end,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::WEDNESDAY->value],
        ], $threeConsecutiveDays->getDaysRanges());

        // One day with 2 consecutive days
        $oneDayWithTwoConsecutiveDays = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            true,
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::THURSDAY->value, ApplicableDayEnum::FRIDAY->value],
            $start,
            $end,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::MONDAY->value],
            ['firstDay' => ApplicableDayEnum::THURSDAY->value, 'lastDay' => ApplicableDayEnum::FRIDAY->value],
        ], $oneDayWithTwoConsecutiveDays->getDaysRanges());

        // One day with one other and 2 consecutive days
        $oneDayWithOneOtherAndTwoConsecutiveDays = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            true,
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::WEDNESDAY->value, ApplicableDayEnum::FRIDAY->value, ApplicableDayEnum::SATURDAY->value],
            $start,
            $end,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::MONDAY->value],
            ['firstDay' => ApplicableDayEnum::WEDNESDAY->value, 'lastDay' => ApplicableDayEnum::WEDNESDAY->value],
            ['firstDay' => ApplicableDayEnum::FRIDAY->value, 'lastDay' => ApplicableDayEnum::SATURDAY->value],
        ], $oneDayWithOneOtherAndTwoConsecutiveDays->getDaysRanges());

        // Monday and sunday
        $mondayAndSunday = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            true,
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::SUNDAY->value],
            $start,
            $end,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::MONDAY->value],
            ['firstDay' => ApplicableDayEnum::SUNDAY->value, 'lastDay' => ApplicableDayEnum::SUNDAY->value],
        ], $mondayAndSunday->getDaysRanges());

        // All days
        $allDays = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            true,
            [
                ApplicableDayEnum::MONDAY->value,
                ApplicableDayEnum::TUESDAY->value,
                ApplicableDayEnum::WEDNESDAY->value,
                ApplicableDayEnum::THURSDAY->value,
                ApplicableDayEnum::FRIDAY->value,
                ApplicableDayEnum::SATURDAY->value,
                ApplicableDayEnum::SUNDAY->value,
            ],
            $start,
            $end,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::SUNDAY->value],
        ], $allDays->getDaysRanges());
    }
}
