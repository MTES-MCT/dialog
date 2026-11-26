<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Period\DeleteTimeSlotCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommandHandler;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\DailyRangeRepositoryInterface;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SavePeriodCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $periodRepository;
    private MockObject $dailyRangeRepository;
    private MockObject $dateUtils;
    private MockObject $commandBus;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->periodRepository = $this->createMock(PeriodRepositoryInterface::class);
        $this->dailyRangeRepository = $this->createMock(DailyRangeRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testCreate(): void
    {
        $startDateTime = new \DateTimeImmutable('2023-05-22');
        $startTime = new \DateTimeImmutable('2023-05-22 10:00:00');
        $endDateTime = new \DateTimeImmutable('2023-05-23');
        $endTime = new \DateTimeImmutable('2023-05-23 16:00:00');

        $mergedStartDateTime = new \DateTimeImmutable('2023-05-22 10:00:00');
        $mergedEndDateTime = new \DateTimeImmutable('2023-05-23 16:00:00');
        $matcher = self::exactly(2);

        $this->dateUtils
            ->expects($matcher)
            ->method('mergeDateAndTime')->willReturnCallback(function (...$parameters) use ($matcher, $startDateTime, $startTime, $endDateTime, $endTime, $mergedStartDateTime, $mergedEndDateTime) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals($startDateTime, $parameters[0]);
                    $this->assertEquals($startTime, $parameters[1]);

                    return $mergedStartDateTime;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals($endDateTime, $parameters[0]);
                    $this->assertEquals($endTime, $parameters[1]);

                    return $mergedEndDateTime;
                }
            });

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdPeriod = $this->createMock(Period::class);
        $createdDailyRange = $this->createMock(DailyRange::class);
        $createdTimeSlot = $this->createMock(TimeSlot::class);
        $measure = $this->createMock(Measure::class);

        $this->dailyRangeRepository
            ->expects(self::never())
            ->method('delete');

        $this->periodRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Period(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        startDateTime: $mergedStartDateTime,
                        endDateTime: $mergedEndDateTime,
                        recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
                        isPublicHolidaysExcluded: true,
                    ),
                ),
            )
            ->willReturn($createdPeriod);

        $createdPeriod
            ->expects(self::once())
            ->method('setDailyRange')
            ->with($createdDailyRange);

        $createdPeriod
            ->expects(self::once())
            ->method('addTimeSlot')
            ->with($createdTimeSlot);

        $dailyRangeCommand = new SaveDailyRangeCommand();
        $dailyRangeCommand->period = $createdPeriod;

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->dailyRangeRepository,
            $this->dateUtils,
            $this->commandBus,
        );

        $timeSlotCommand = new SaveTimeSlotCommand();
        $timeSlotCommand->period = $createdPeriod;
        $matcher = self::exactly(2);

        $this->commandBus
            ->expects($matcher)
            ->method('handle')->willReturnCallback(function (...$parameters) use ($matcher, $timeSlotCommand, $dailyRangeCommand, $createdTimeSlot, $createdDailyRange) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals($timeSlotCommand, $parameters[0]);

                    return $createdTimeSlot;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals($dailyRangeCommand, $parameters[0]);

                    return $createdDailyRange;
                }
            });

        $command = new SavePeriodCommand();
        $command->measure = $measure;
        $command->startDate = $startDateTime;
        $command->endDate = $endDateTime;
        $command->startTime = $startTime;
        $command->endTime = $endTime;
        $command->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $command->dailyRange = $dailyRangeCommand;
        $command->timeSlots = [$timeSlotCommand];
        $command->isPublicHolidaysExcluded = true;
        $result = $handler($command);

        $this->assertSame($createdPeriod, $result);
    }

    public function testCreateWithoutStartTimeAndEndDate(): void
    {
        $startDateTime = new \DateTimeImmutable('2023-05-22');

        $this->dateUtils
            ->expects(self::never())
            ->method('mergeDateAndTime');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdPeriod = $this->createMock(Period::class);
        $createdDailyRange = $this->createMock(DailyRange::class);
        $createdTimeSlot = $this->createMock(TimeSlot::class);
        $measure = $this->createMock(Measure::class);

        $this->dailyRangeRepository
            ->expects(self::never())
            ->method('delete');

        $this->periodRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Period(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        startDateTime: $startDateTime,
                        endDateTime: null,
                        recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
                    ),
                ),
            )
            ->willReturn($createdPeriod);

        $createdPeriod
            ->expects(self::once())
            ->method('setDailyRange')
            ->with($createdDailyRange);

        $createdPeriod
            ->expects(self::once())
            ->method('addTimeSlot')
            ->with($createdTimeSlot);

        $dailyRangeCommand = new SaveDailyRangeCommand();
        $dailyRangeCommand->period = $createdPeriod;

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->dailyRangeRepository,
            $this->dateUtils,
            $this->commandBus,
        );

        $timeSlotCommand = new SaveTimeSlotCommand();
        $timeSlotCommand->period = $createdPeriod;
        $matcher = self::exactly(2);

        $this->commandBus
            ->expects($matcher)
            ->method('handle')->willReturnCallback(function (...$parameters) use ($matcher, $timeSlotCommand, $dailyRangeCommand, $createdTimeSlot, $createdDailyRange) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals($timeSlotCommand, $parameters[0]);

                    return $createdTimeSlot;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals($dailyRangeCommand, $parameters[0]);

                    return $createdDailyRange;
                }
            });

        $command = new SavePeriodCommand();
        $command->isPermanent = true;
        $command->measure = $measure;
        $command->startDate = $startDateTime;
        $command->endDate = null;
        $command->startTime = null;
        $command->endTime = null;
        $command->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $command->dailyRange = $dailyRangeCommand;
        $command->timeSlots = [$timeSlotCommand];
        $result = $handler($command);

        $this->assertSame($createdPeriod, $result);
    }

    public function testUpdate(): void
    {
        $startDateTime = new \DateTimeImmutable('2023-05-22');
        $startTime = new \DateTimeImmutable('2023-05-22 10:00:00');
        $endDateTime = new \DateTimeImmutable('2023-05-23');
        $endTime = new \DateTimeImmutable('2023-05-23 16:00:00');

        $mergedStartDateTime = new \DateTimeImmutable('2023-05-22 10:00:00');
        $mergedEndDateTime = new \DateTimeImmutable('2023-05-23 16:00:00');
        $matcher = self::exactly(2);

        $this->dateUtils
            ->expects($matcher)
            ->method('mergeDateAndTime')->willReturnCallback(function (...$parameters) use ($matcher, $startDateTime, $startTime, $endDateTime, $endTime, $mergedStartDateTime, $mergedEndDateTime) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals($startDateTime, $parameters[0]);
                    $this->assertEquals($startTime, $parameters[1]);

                    return $mergedStartDateTime;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals($endDateTime, $parameters[0]);
                    $this->assertEquals($endTime, $parameters[1]);

                    return $mergedEndDateTime;
                }
            });

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->periodRepository
            ->expects(self::never())
            ->method('add');

        $this->dailyRangeRepository
            ->expects(self::never())
            ->method('delete');

        $timeSlot1 = $this->createMock(TimeSlot::class);
        $timeSlot1
            ->expects(self::exactly(2))
            ->method('getUuid')
            ->willReturn('e7ceb504-e6d2-43b7-9e1f-43f4baa17907');

        $timeSlot2 = $this->createMock(TimeSlot::class);
        $timeSlot2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('28accfd6-d896-4ed9-96a3-1754f288f511');

        $period = $this->createMock(Period::class);
        $period
            ->expects(self::once())
            ->method('update')
            ->with($mergedStartDateTime, PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value, $mergedEndDateTime, true);

        $period
            ->expects(self::exactly(3))
            ->method('getTimeSlots')
            ->willReturn([$timeSlot1, $timeSlot2]);
        $period
            ->expects(self::once())
            ->method('removeTimeSlot')
            ->with($timeSlot2);

        $timeSlotCommand1 = new SaveTimeSlotCommand($timeSlot1);
        $timeSlotCommand1->period = $period;

        $timeSlotCommand2 = new SaveTimeSlotCommand($timeSlot2);
        $timeSlotCommand2->period = $period;

        $dailyRangeCommand = new SaveDailyRangeCommand();
        $dailyRangeCommand->period = $period;
        $matcher = self::exactly(3);

        $this->commandBus
            ->expects($matcher)
            ->method('handle')->willReturnCallback(function (...$parameters) use ($matcher, $dailyRangeCommand, $timeSlotCommand1, $timeSlot2) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals($dailyRangeCommand, $parameters[0]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals($timeSlotCommand1, $parameters[0]);
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertEquals(new DeleteTimeSlotCommand($timeSlot2), $parameters[0]);
                }
            });

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->dailyRangeRepository,
            $this->dateUtils,
            $this->commandBus,
        );

        $command = new SavePeriodCommand($period);
        $command->startDate = $startDateTime;
        $command->endDate = $endDateTime;
        $command->startTime = $startTime;
        $command->endTime = $endTime;
        $command->isPublicHolidaysExcluded = true;
        $command->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $command->dailyRange = $dailyRangeCommand;
        $command->timeSlots = [$timeSlotCommand1];

        $result = $handler($command);

        $this->assertSame($period, $result);
    }

    public function testUpdateWithoutDailyRange(): void
    {
        $startDateTime = new \DateTimeImmutable('2023-05-22');
        $startTime = new \DateTimeImmutable('2023-05-22 10:00:00');
        $endDateTime = new \DateTimeImmutable('2023-05-23');
        $endTime = new \DateTimeImmutable('2023-05-23 16:00:00');

        $mergedStartDateTime = new \DateTimeImmutable('2023-05-22 10:00:00');
        $mergedEndDateTime = new \DateTimeImmutable('2023-05-23 16:00:00');

        $oldDailyRange = $this->createMock(DailyRange::class);
        $matcher = self::exactly(2);

        $this->dateUtils
            ->expects($matcher)
            ->method('mergeDateAndTime')->willReturnCallback(function (...$parameters) use ($matcher, $startDateTime, $startTime, $endDateTime, $endTime, $mergedStartDateTime, $mergedEndDateTime) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals($startDateTime, $parameters[0]);
                    $this->assertEquals($startTime, $parameters[1]);

                    return $mergedStartDateTime;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals($endDateTime, $parameters[0]);
                    $this->assertEquals($endTime, $parameters[1]);

                    return $mergedEndDateTime;
                }
            });

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->periodRepository
            ->expects(self::never())
            ->method('add');

        $this->dailyRangeRepository
            ->expects(self::once())
            ->method('delete')
            ->with($oldDailyRange);

        $period = $this->createMock(Period::class);
        $period
            ->expects(self::once())
            ->method('update')
            ->with($mergedStartDateTime, PeriodRecurrenceTypeEnum::EVERY_DAY->value, $mergedEndDateTime);
        $period
            ->expects(self::exactly(3))
            ->method('getDailyRange')
            ->willReturn($oldDailyRange);
        $period
            ->expects(self::once())
            ->method('setDailyRange')
            ->with(null);

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->dailyRangeRepository,
            $this->dateUtils,
            $this->commandBus,
        );
        $command = new SavePeriodCommand($period);
        $command->startDate = $startDateTime;
        $command->endDate = $endDateTime;
        $command->startTime = $startTime;
        $command->endTime = $endTime;
        $command->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $command->timeSlots = [];

        $result = $handler($command);

        $this->assertSame($period, $result);
    }
}
