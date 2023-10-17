<?php

declare(strict_types=1);

namespace app\Tests\Unit\Domain\Condition\Period;

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
        $dailyRange = new DailyRange(
            '0da3ebbc-5dea-4b57-86aa-15ff3f192492',
            $days,
            $period,
        );

        $this->assertSame('0da3ebbc-5dea-4b57-86aa-15ff3f192492', $dailyRange->getUuid());
        $this->assertSame($days, $dailyRange->getApplicableDays());
        $this->assertSame($period, $dailyRange->getPediod());
        $this->assertEmpty($dailyRange->getTimeSlots());
    }
}
