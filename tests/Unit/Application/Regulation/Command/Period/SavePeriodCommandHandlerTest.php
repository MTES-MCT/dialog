<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommandHandler;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SavePeriodCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $periodRepository;
    private $dateUtils;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->periodRepository = $this->createMock(PeriodRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testCreate(): void
    {
        $startDate = new \DateTimeImmutable('2023-05-22');
        $startHour = new \DateTimeImmutable('2023-05-22 10:00:00');
        $endDate = new \DateTimeImmutable('2023-05-23');
        $endHour = new \DateTimeImmutable('2023-05-23 16:00:00');

        $mergedStartDate = new \DateTimeImmutable('2023-05-22 10:00:00');
        $mergedEndDate = new \DateTimeImmutable('2023-05-23 16:00:00');

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('mergeDateAndTimeOfTwoDates')
            ->withConsecutive([$startDate, $startHour], [$endDate, $endHour])
            ->willReturnOnConsecutiveCalls($mergedStartDate, $mergedEndDate);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdPeriod = $this->createMock(Period::class);
        $measure = $this->createMock(Measure::class);

        $this->periodRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Period(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        applicableDays: [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::WEDNESDAY->value],
                        startDate: $mergedStartDate,
                        endDate: $mergedEndDate,
                        startTime: $mergedStartDate,
                        endTime: $mergedEndDate,
                        recurrenceType: PeriodRecurrenceTypeEnum::SOME_DAYS->value,
                    ),
                ),
            )
            ->willReturn($createdPeriod);

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->dateUtils,
        );

        $command = new SavePeriodCommand();
        $command->measure = $measure;
        $command->applicableDays = [ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::WEDNESDAY->value];
        $command->startDate = $startDate;
        $command->endDate = $endDate;
        $command->startHour = $startHour;
        $command->endHour = $endHour;
        $command->recurrenceType = PeriodRecurrenceTypeEnum::SOME_DAYS->value;

        $result = $handler($command);

        $this->assertSame($createdPeriod, $result);
    }

    public function testCreateApplicableDaysWithEveryDaysRecurrence(): void
    {
        $startDate = new \DateTimeImmutable('2023-05-22');
        $startHour = new \DateTimeImmutable('2023-05-22 10:00:00');
        $endDate = new \DateTimeImmutable('2023-05-23');
        $endHour = new \DateTimeImmutable('2023-05-23 16:00:00');

        $mergedStartDate = new \DateTimeImmutable('2023-05-22 10:00:00');
        $mergedEndDate = new \DateTimeImmutable('2023-05-23 16:00:00');

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('mergeDateAndTimeOfTwoDates')
            ->withConsecutive([$startDate, $startHour], [$endDate, $endHour])
            ->willReturnOnConsecutiveCalls($mergedStartDate, $mergedEndDate);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdPeriod = $this->createMock(Period::class);
        $measure = $this->createMock(Measure::class);

        $this->periodRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Period(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        measure: $measure,
                        applicableDays: [],
                        startDate: $mergedStartDate,
                        endDate: $mergedEndDate,
                        startTime: $mergedStartDate,
                        endTime: $mergedEndDate,
                        recurrenceType: PeriodRecurrenceTypeEnum::EVERY_DAYS->value,
                    ),
                ),
            )
            ->willReturn($createdPeriod);

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->dateUtils,
        );

        $command = new SavePeriodCommand();
        $command->measure = $measure;
        $command->applicableDays = [ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::WEDNESDAY->value];
        $command->startDate = $startDate;
        $command->endDate = $endDate;
        $command->startHour = $startHour;
        $command->endHour = $endHour;
        $command->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAYS->value;

        $result = $handler($command);

        $this->assertSame($createdPeriod, $result);
    }

    public function testUpdate(): void
    {
        $startDate = new \DateTimeImmutable('2023-05-22');
        $startHour = new \DateTimeImmutable('2023-05-22 10:00:00');
        $endDate = new \DateTimeImmutable('2023-05-23');
        $endHour = new \DateTimeImmutable('2023-05-23 16:00:00');

        $mergedStartDate = new \DateTimeImmutable('2023-05-22 10:00:00');
        $mergedEndDate = new \DateTimeImmutable('2023-05-23 16:00:00');

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('mergeDateAndTimeOfTwoDates')
            ->withConsecutive([$startDate, $startHour], [$endDate, $endHour])
            ->willReturnOnConsecutiveCalls($mergedStartDate, $mergedEndDate);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->periodRepository
            ->expects(self::never())
            ->method('add');

        $period = $this->createMock(Period::class);
        $period
            ->expects(self::once())
            ->method('update')
            ->with([], $mergedStartDate, $mergedEndDate, $mergedStartDate, $mergedEndDate, PeriodRecurrenceTypeEnum::EVERY_DAYS->value);

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->dateUtils,
        );

        $command = new SavePeriodCommand($period);
        $command->applicableDays = [];
        $command->startDate = $startDate;
        $command->endDate = $endDate;
        $command->startHour = $startHour;
        $command->endHour = $endHour;
        $command->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAYS->value;

        $result = $handler($command);

        $this->assertSame($period, $result);
    }
}
