<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\DayWeekMonth;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\ApplicableMonthEnum;
use App\Domain\Condition\Period\Period;
use PHPUnit\Framework\TestCase;

final class DayWeekMonthTest extends TestCase
{
    public function testGetters(): void
    {
        $period = $this->createMock(Period::class);
        $dayWeekMonth = new DayWeekMonth(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $period,
            ApplicableDayEnum::MONDAY,
            ApplicableMonthEnum::DECEMBER
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $dayWeekMonth->getUuid());
        $this->assertSame(ApplicableDayEnum::MONDAY, $dayWeekMonth->getApplicableDay());
        $this->assertSame(ApplicableMonthEnum::DECEMBER, $dayWeekMonth->getApplicableMonth());
        $this->assertSame($period, $dayWeekMonth->getPeriod());
    }
}
