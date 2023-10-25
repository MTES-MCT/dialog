<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommandHandler;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Condition\Period\Period;
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

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('mergeDateAndTime')
            ->withConsecutive([$startDateTime, $startTime], [$endDateTime, $endTime])
            ->willReturnOnConsecutiveCalls($mergedStartDateTime, $mergedEndDateTime);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdPeriod = $this->createMock(Period::class);
        $createdDailyRange = $this->createMock(DailyRange::class);
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
                    ),
                ),
            )
            ->willReturn($createdPeriod);

        $createdPeriod
            ->expects(self::once())
            ->method('setDailyRange')
            ->with($createdDailyRange);

        $dailyRangeCommand = new SaveDailyRangeCommand();
        $dailyRangeCommand->period = $createdPeriod;

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($dailyRangeCommand)
            ->willReturn($createdDailyRange);

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->dailyRangeRepository,
            $this->dateUtils,
            $this->commandBus,
        );

        $command = new SavePeriodCommand();
        $command->measure = $measure;
        $command->startDate = $startDateTime;
        $command->endDate = $endDateTime;
        $command->startTime = $startTime;
        $command->endTime = $endTime;
        $command->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $command->dailyRange = $dailyRangeCommand;
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

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('mergeDateAndTime')
            ->withConsecutive([$startDateTime, $startTime], [$endDateTime, $endTime])
            ->willReturnOnConsecutiveCalls($mergedStartDateTime, $mergedEndDateTime);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->periodRepository
            ->expects(self::never())
            ->method('add');

        $this->dailyRangeRepository
            ->expects(self::never())
            ->method('delete');

        $period = $this->createMock(Period::class);
        $period
            ->expects(self::once())
            ->method('update')
            ->with($mergedStartDateTime, $mergedEndDateTime, PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value);

        $dailyRangeCommand = new SaveDailyRangeCommand();
        $dailyRangeCommand->period = $period;

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($dailyRangeCommand);

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
        $command->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
        $command->dailyRange = $dailyRangeCommand;

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

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('mergeDateAndTime')
            ->withConsecutive([$startDateTime, $startTime], [$endDateTime, $endTime])
            ->willReturnOnConsecutiveCalls($mergedStartDateTime, $mergedEndDateTime);

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
            ->with($mergedStartDateTime, $mergedEndDateTime, PeriodRecurrenceTypeEnum::EVERY_DAY->value);
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

        $result = $handler($command);

        $this->assertSame($period, $result);
    }
}
