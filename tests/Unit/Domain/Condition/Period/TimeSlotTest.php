<?php

declare(strict_types=1);

namespace app\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\TimeSlot;
use PHPUnit\Framework\TestCase;

final class TimeSlotTest extends TestCase
{
    public function testGetters(): void
    {
        $dailyRange = $this->createMock(DailyRange::class);
        $start = new \DateTimeImmutable('2022-12-20 09:00:00');
        $end = new \DateTimeImmutable('2022-12-20 18:00:00');

        $timeSlot = new TimeSlot(
            '69549a87-82b1-4835-9334-fc2881983e40',
            $dailyRange,
            $start,
            $end,
        );

        $this->assertSame('69549a87-82b1-4835-9334-fc2881983e40', $timeSlot->getUuid());
        $this->assertSame($dailyRange, $timeSlot->getDailyRange());
        $this->assertSame($start, $timeSlot->getStartTime());
        $this->assertSame($end, $timeSlot->getEndTime());
    }
}
