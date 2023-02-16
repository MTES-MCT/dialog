<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimePeriodOfDay;
use PHPUnit\Framework\TestCase;

final class TimePeriodOfDayTest extends TestCase
{
    public function testGetters(): void
    {
        $period = $this->createMock(Period::class);
        $start = new \DateTimeImmutable('2022-12-20 09:00:00');
        $end = new \DateTimeImmutable('2022-12-20 18:00:00');
        $timePeriodOfDay = new TimePeriodOfDay(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $start,
            $end,
            $period
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $timePeriodOfDay->getUuid());
        $this->assertSame($start, $timePeriodOfDay->getStartTime());
        $this->assertSame($end, $timePeriodOfDay->getEndTime());
        $this->assertSame($period, $timePeriodOfDay->getPeriod());
    }
}
