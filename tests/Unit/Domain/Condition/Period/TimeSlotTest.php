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

        $start2 = new \DateTimeImmutable('2022-12-20 09:30:00');
        $end2 = new \DateTimeImmutable('2022-12-20 18:30:00');
        $timeSlot->update($start2, $end2);
        $this->assertSame($start2, $timeSlot->getStartTime());
        $this->assertSame($end2, $timeSlot->getEndTime());
    }
}
