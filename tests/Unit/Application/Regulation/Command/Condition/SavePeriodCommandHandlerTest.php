<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Condition;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Condition\SavePeriodCommand;
use App\Application\Regulation\Command\Condition\SavePeriodCommandHandler;
use App\Domain\Condition\Condition;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\ConditionRepositoryInterface;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SavePeriodCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $periodRepository;
    private $conditionRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->periodRepository = $this->createMock(PeriodRepositoryInterface::class);
        $this->conditionRepository = $this->createMock(ConditionRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $start = new \DateTimeImmutable('2023-05-22 10:00:00');
        $end = new \DateTimeImmutable('2023-05-22 16:00:00');

        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn(
                'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                '7fb74c5d-069b-4027-b994-7545bb0942d0',
            );

        $createdPeriod = $this->createMock(Period::class);
        $createdCondition = $this->createMock(Condition::class);
        $measure = $this->createMock(Measure::class);

        $this->conditionRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Condition(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        negate: false,
                        measure: $measure,
                    ),
                ),
            )
            ->willReturn($createdCondition);

        $this->periodRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new Period(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        condition: $createdCondition,
                        includeHolidays: true,
                        applicableDays: [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value],
                        startTime: $start,
                        endTime: $end,
                    ),
                ),
            )
            ->willReturn($createdPeriod);

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->conditionRepository,
        );

        $command = new SavePeriodCommand();
        $command->measure = $measure;
        $command->startTime = $start;
        $command->endTime = $end;
        $command->applicableDays = [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value];
        $command->includeHolidays = true;

        $result = $handler($command);

        $this->assertSame($createdPeriod, $result);
    }

    public function testUpdate(): void
    {
        $start = new \DateTimeImmutable('2023-05-22 10:00:00');
        $end = new \DateTimeImmutable('2023-05-22 16:00:00');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $period = $this->createMock(Period::class);

        $period
            ->expects(self::once())
            ->method('update')
            ->with(true, [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value], $start, $end);

        $this->conditionRepository
            ->expects(self::never())
            ->method('add');

        $this->periodRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
            $this->conditionRepository,
        );

        $command = new SavePeriodCommand($period);
        $command->startTime = $start;
        $command->endTime = $end;
        $command->applicableDays = [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value];
        $command->includeHolidays = true;

        $result = $handler($command);

        $this->assertSame($period, $result);
    }
}
