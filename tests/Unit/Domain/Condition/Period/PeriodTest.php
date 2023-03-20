<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\ApplicableMonthEnum;
use App\Domain\Condition\Period\Enum\SpecialDayTypeEnum;
use App\Domain\Condition\Period\Period;
use PHPUnit\Framework\TestCase;

final class PeriodTest extends TestCase
{
    public function testGetters(): void
    {
        $start = new \DateTimeImmutable('2022-12-20 09:00:00');
        $end = new \DateTimeImmutable('2022-12-20 18:00:00');
        $period = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            [ApplicableDayEnum::MONDAY, ApplicableDayEnum::THURSDAY],
            [ApplicableMonthEnum::JANUARY, ApplicableMonthEnum::FEBRUARY],
            [SpecialDayTypeEnum::PUBLIC_HOLIDAY],
            $start,
            $end,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $period->getUuid());
        $this->assertSame([ApplicableDayEnum::MONDAY, ApplicableDayEnum::THURSDAY], $period->getApplicableDays());
        $this->assertSame([ApplicableMonthEnum::JANUARY, ApplicableMonthEnum::FEBRUARY], $period->getApplicableMonths());
        $this->assertSame([SpecialDayTypeEnum::PUBLIC_HOLIDAY], $period->getSpecialDays());
        $this->assertSame($start, $period->getDayStartTime());
        $this->assertSame($end, $period->getDayEndTime());
        $this->assertSame(null, $period->getOverallValidPeriod()); // automatically set by Doctrine
        $this->assertSame(null, $period->getOverallExceptionPeriod()); // automatically set by Doctrine
    }
}
