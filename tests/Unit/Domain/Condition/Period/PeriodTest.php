<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Period;

use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Measure;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class PeriodTest extends TestCase
{
    public function testGetters(): void
    {
        $timeSlot1 = $this->createMock(TimeSlot::class);
        $timeSlot2 = $this->createMock(TimeSlot::class);
        $timeSlot3 = $this->createMock(TimeSlot::class);

        $dailyRange = $this->createMock(DailyRange::class);
        $measure = $this->createMock(Measure::class);
        $start = new \DateTimeImmutable('2022-12-20 09:00:00');
        $end = new \DateTimeImmutable('2022-12-20 18:00:00');
        $period = new Period(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            $start,
            $end,
            PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
            $dailyRange,
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $period->getUuid());
        $this->assertSame($start, $period->getStartDateTime());
        $this->assertSame($end, $period->getEndDateTime());
        $this->assertSame($measure, $period->getMeasure());
        $this->assertSame($dailyRange, $period->getDailyRange());
        $this->assertSame(PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value, $period->getRecurrenceType());
        $this->assertEmpty($period->getTimeSlots());

        $period->addTimeSlot($timeSlot1);
        $period->addTimeSlot($timeSlot1); // Test dupliucate
        $period->addTimeSlot($timeSlot2);

        $this->assertEquals($period->getTimeSlots(), new ArrayCollection([$timeSlot1, $timeSlot2]));

        $period->removeTimeSlot($timeSlot1);
        $period->removeTimeSlot($timeSlot3); // Timeslot that does not belong to the dailyRange
        $this->assertEquals($period->getTimeSlots(), new ArrayCollection([1 => $timeSlot2]));

        $newStart = new \DateTimeImmutable('2022-12-20 09:00:00');
        $newEnd = new \DateTimeImmutable('2022-12-20 18:00:00');
        $period->update(
            $newStart,
            PeriodRecurrenceTypeEnum::EVERY_DAY->value,
            $newEnd,
        );

        $this->assertSame($newStart, $period->getStartDateTime());
        $this->assertSame($newEnd, $period->getEndDateTime());
        $this->assertSame(PeriodRecurrenceTypeEnum::EVERY_DAY->value, $period->getRecurrenceType());
    }
}
