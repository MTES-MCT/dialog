<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Period\DeleteTimeSlotCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommandHandler;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Repository\DailyRangeRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveDailyRangeCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $dailyRangeRepository;
    private $commandBus;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->dailyRangeRepository = $this->createMock(DailyRangeRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('d035fec0-30f3-4134-95b9-d74c68eb53e3');

        $createdTimeSlot = $this->createMock(TimeSlot::class);
        $createdDailyRange = $this->createMock(DailyRange::class);
        $period = $this->createMock(Period::class);

        $createdDailyRange
            ->expects(self::once())
            ->method('addTimeSlot')
            ->with($createdTimeSlot);

        $this->dailyRangeRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new DailyRange(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        applicableDays: [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::WEDNESDAY->value],
                        period: $period,
                    ),
                ),
            )
            ->willReturn($createdDailyRange);

        $timeSlotCommand = new SaveTimeSlotCommand();
        $timeSlotCommand->dailyRange = $createdDailyRange;

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($timeSlotCommand))
            ->willReturn($createdTimeSlot);

        $handler = new SaveDailyRangeCommandHandler(
            $this->idFactory,
            $this->dailyRangeRepository,
            $this->commandBus,
        );

        $command = new SaveDailyRangeCommand();
        $command->period = $period;
        $command->applicableDays = [ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::WEDNESDAY->value];
        $command->timeSlots = [$timeSlotCommand];

        $result = $handler($command);

        $this->assertSame($createdDailyRange, $result);
    }

    public function testUpdate(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

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

        $this->dailyRangeRepository
            ->expects(self::never())
            ->method('add');

        $period = $this->createMock(Period::class);
        $dailyRange = $this->createMock(DailyRange::class);
        $dailyRange
            ->expects(self::once())
            ->method('update')
            ->with([ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::WEDNESDAY->value]);
        $dailyRange
            ->expects(self::exactly(2))
            ->method('getTimeSlots')
            ->willReturn([$timeSlot1, $timeSlot2]);
        $dailyRange
            ->expects(self::once())
            ->method('removeTimeSlot')
            ->with($timeSlot2);

        $timeSlotCommand1 = new SaveTimeSlotCommand($timeSlot1);
        $timeSlotCommand1->dailyRange = $dailyRange;

        $timeSlotCommand2 = new SaveTimeSlotCommand($timeSlot2);
        $timeSlotCommand2->dailyRange = $dailyRange;

        $this->commandBus
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$this->equalTo($timeSlotCommand1)], [$this->equalTo(new DeleteTimeSlotCommand($timeSlot2))]);

        $handler = new SaveDailyRangeCommandHandler(
            $this->idFactory,
            $this->dailyRangeRepository,
            $this->commandBus,
        );

        $command = new SaveDailyRangeCommand($dailyRange);
        $command->period = $period;
        $command->applicableDays = [ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::WEDNESDAY->value];
        $command->timeSlots = [$timeSlotCommand1];

        $result = $handler($command);

        $this->assertSame($dailyRange, $result);
    }
}
