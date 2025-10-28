<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Mapper\Transformers;

use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Infrastructure\DTO\Event\SaveDailyRangeDTO;
use App\Infrastructure\DTO\Event\SavePeriodDTO;
use App\Infrastructure\DTO\Event\SaveTimeSlotDTO;
use App\Infrastructure\Mapper\Transformers\PeriodsTransformer;
use PHPUnit\Framework\TestCase;

final class PeriodsTransformerTest extends TestCase
{
    public function testTransformsBasicPeriod(): void
    {
        $dto = new SavePeriodDTO();
        $dto->startDate = '2025-01-15';
        $dto->startTime = '2025-01-15T08:00:00Z';
        $dto->endDate = '2025-01-20';
        $dto->endTime = '2025-01-20T18:00:00Z';
        $dto->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY;
        $dto->isPermanent = false;

        $commands = PeriodsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertSame(PeriodRecurrenceTypeEnum::EVERY_DAY->value, $cmd->recurrenceType);
        self::assertFalse($cmd->isPermanent);
        self::assertInstanceOf(\DateTimeImmutable::class, $cmd->startDate);
        self::assertSame('2025-01-15', $cmd->startDate->format('Y-m-d'));
        self::assertInstanceOf(\DateTimeImmutable::class, $cmd->startTime);
        self::assertSame('2025-01-15T08:00:00+00:00', $cmd->startTime->format('c'));
        self::assertInstanceOf(\DateTimeImmutable::class, $cmd->endDate);
        self::assertSame('2025-01-20', $cmd->endDate->format('Y-m-d'));
        self::assertInstanceOf(\DateTimeImmutable::class, $cmd->endTime);
        self::assertSame('2025-01-20T18:00:00+00:00', $cmd->endTime->format('c'));
    }

    public function testTransformsPeriodWithDailyRange(): void
    {
        $dto = new SavePeriodDTO();
        $dto->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS;
        $dto->isPermanent = true;

        $dailyRangeDto = new SaveDailyRangeDTO();
        $dailyRangeDto->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS;
        $dailyRangeDto->applicableDays = ['monday', 'tuesday', 'wednesday'];
        $dto->dailyRange = $dailyRangeDto;

        $commands = PeriodsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertNotNull($cmd->dailyRange);
        self::assertSame(PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value, $cmd->dailyRange->recurrenceType);
        self::assertSame(['monday', 'tuesday', 'wednesday'], $cmd->dailyRange->applicableDays);
    }

    public function testTransformsPeriodWithTimeSlots(): void
    {
        $dto = new SavePeriodDTO();
        $dto->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY;

        $timeSlot1 = new SaveTimeSlotDTO();
        $timeSlot1->startTime = '2025-01-15T08:00:00Z';
        $timeSlot1->endTime = '2025-01-15T12:00:00Z';

        $timeSlot2 = new SaveTimeSlotDTO();
        $timeSlot2->startTime = '2025-01-15T14:00:00Z';
        $timeSlot2->endTime = '2025-01-15T18:00:00Z';

        $dto->timeSlots = [$timeSlot1, $timeSlot2];

        $commands = PeriodsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertIsArray($cmd->timeSlots);
        self::assertCount(2, $cmd->timeSlots);

        $ts1 = $cmd->timeSlots[0];
        self::assertInstanceOf(\DateTimeImmutable::class, $ts1->startTime);
        self::assertSame('2025-01-15T08:00:00+00:00', $ts1->startTime->format('c'));
        self::assertInstanceOf(\DateTimeImmutable::class, $ts1->endTime);
        self::assertSame('2025-01-15T12:00:00+00:00', $ts1->endTime->format('c'));

        $ts2 = $cmd->timeSlots[1];
        self::assertInstanceOf(\DateTimeImmutable::class, $ts2->startTime);
        self::assertSame('2025-01-15T14:00:00+00:00', $ts2->startTime->format('c'));
        self::assertInstanceOf(\DateTimeImmutable::class, $ts2->endTime);
        self::assertSame('2025-01-15T18:00:00+00:00', $ts2->endTime->format('c'));
    }

    public function testSkipsInvalidTimeSlotItems(): void
    {
        $dto = new SavePeriodDTO();
        $dto->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY;

        $validTimeSlot = new SaveTimeSlotDTO();
        $validTimeSlot->startTime = '2025-01-15T08:00:00Z';
        $validTimeSlot->endTime = '2025-01-15T12:00:00Z';

        $dto->timeSlots = [
            $validTimeSlot,
            new \stdClass(), // Invalid item
            'invalid', // Invalid item
        ];

        $commands = PeriodsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertIsArray($cmd->timeSlots);
        self::assertCount(1, $cmd->timeSlots); // Only valid time slot
    }

    public function testHandlesNullValues(): void
    {
        $dto = new SavePeriodDTO();
        $dto->startDate = null;
        $dto->startTime = null;
        $dto->endDate = null;
        $dto->endTime = null;
        $dto->recurrenceType = null;
        $dto->isPermanent = null;
        $dto->dailyRange = null;
        $dto->timeSlots = null;

        $commands = PeriodsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertNull($cmd->startDate);
        self::assertNull($cmd->startTime);
        self::assertNull($cmd->endDate);
        self::assertNull($cmd->endTime);
        self::assertNull($cmd->recurrenceType);
        self::assertNull($cmd->isPermanent);
        self::assertNull($cmd->dailyRange);
        self::assertEmpty($cmd->timeSlots);
    }

    public function testHandlesInvalidDateTimeValues(): void
    {
        $dto = new SavePeriodDTO();
        $dto->startDate = 'invalid-date';
        $dto->startTime = 'invalid-time';
        $dto->endDate = '2025-13-45'; // Invalid date
        $dto->endTime = 'invalid-time';

        $commands = PeriodsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertNull($cmd->startDate);
        self::assertNull($cmd->startTime);
        self::assertNull($cmd->endDate);
        self::assertNull($cmd->endTime);
    }
}
