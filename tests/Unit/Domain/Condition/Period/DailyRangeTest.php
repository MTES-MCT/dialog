<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use PHPUnit\Framework\TestCase;

final class DailyRangeTest extends TestCase
{
    public function testGetters(): void
    {
        $period = $this->createMock(Period::class);
        $days = [
            ApplicableDayEnum::MONDAY->value,
            ApplicableDayEnum::TUESDAY->value,
            ApplicableDayEnum::WEDNESDAY->value,
            ApplicableDayEnum::FRIDAY->value,
        ];
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
        $dailyRange = new DailyRange(
            '0da3ebbc-5dea-4b57-86aa-15ff3f192492',
            $days,
            $period,
        );

        $this->assertSame('0da3ebbc-5dea-4b57-86aa-15ff3f192492', $dailyRange->getUuid());
        $this->assertSame($days, $dailyRange->getApplicableDays());
        $this->assertSame($daysRange, $dailyRange->getDaysRanges());
        $this->assertSame($period, $dailyRange->getPediod());

        $updatedDaysRange = [
            [
                'firstDay' => ApplicableDayEnum::WEDNESDAY->value,
                'lastDay' => ApplicableDayEnum::WEDNESDAY->value,
            ],
        ];

        $dailyRange->update([ApplicableDayEnum::WEDNESDAY->value]);

        $this->assertSame([ApplicableDayEnum::WEDNESDAY->value], $dailyRange->getApplicableDays());
        $this->assertSame($updatedDaysRange, $dailyRange->getDaysRanges());
    }

    public function testDaysRanges(): void
    {
        $period = $this->createMock(Period::class);

        // One day
        $oneDay = new DailyRange(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            [ApplicableDayEnum::MONDAY->value],
            $period,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::MONDAY->value],
        ], $oneDay->getDaysRanges());

        // Two consecutives days
        $twoConsecutiveDays = new DailyRange(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value],
            $period,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::TUESDAY->value],
        ], $twoConsecutiveDays->getDaysRanges());

        // Three consecutive days
        $threeConsecutiveDays = new DailyRange(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::WEDNESDAY->value],
            $period,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::WEDNESDAY->value],
        ], $threeConsecutiveDays->getDaysRanges());

        // One day with 2 consecutive days
        $oneDayWithTwoConsecutiveDays = new DailyRange(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::THURSDAY->value, ApplicableDayEnum::FRIDAY->value],
            $period,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::MONDAY->value],
            ['firstDay' => ApplicableDayEnum::THURSDAY->value, 'lastDay' => ApplicableDayEnum::FRIDAY->value],
        ], $oneDayWithTwoConsecutiveDays->getDaysRanges());

        // One day with one other and 2 consecutive days
        $oneDayWithOneOtherAndTwoConsecutiveDays = new DailyRange(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::WEDNESDAY->value, ApplicableDayEnum::FRIDAY->value, ApplicableDayEnum::SATURDAY->value],
            $period,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::MONDAY->value],
            ['firstDay' => ApplicableDayEnum::WEDNESDAY->value, 'lastDay' => ApplicableDayEnum::WEDNESDAY->value],
            ['firstDay' => ApplicableDayEnum::FRIDAY->value, 'lastDay' => ApplicableDayEnum::SATURDAY->value],
        ], $oneDayWithOneOtherAndTwoConsecutiveDays->getDaysRanges());

        // Monday and sunday
        $mondayAndSunday = new DailyRange(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::SUNDAY->value],
            $period,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::MONDAY->value],
            ['firstDay' => ApplicableDayEnum::SUNDAY->value, 'lastDay' => ApplicableDayEnum::SUNDAY->value],
        ], $mondayAndSunday->getDaysRanges());

        // All days
        $allDays = new DailyRange(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            [
                ApplicableDayEnum::MONDAY->value,
                ApplicableDayEnum::TUESDAY->value,
                ApplicableDayEnum::WEDNESDAY->value,
                ApplicableDayEnum::THURSDAY->value,
                ApplicableDayEnum::FRIDAY->value,
                ApplicableDayEnum::SATURDAY->value,
                ApplicableDayEnum::SUNDAY->value,
            ],
            $period,
        );
        $this->assertSame([
            ['firstDay' => ApplicableDayEnum::MONDAY->value, 'lastDay' => ApplicableDayEnum::SUNDAY->value],
        ], $allDays->getDaysRanges());
    }
}
