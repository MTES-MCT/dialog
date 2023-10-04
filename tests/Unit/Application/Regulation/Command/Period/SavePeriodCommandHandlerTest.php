<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommandHandler;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SavePeriodCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $periodRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->periodRepository = $this->createMock(PeriodRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $start = new \DateTimeImmutable('2023-05-22 10:00:00');
        $end = new \DateTimeImmutable('2023-05-22 16:00:00');

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
                        startTime: $start,
                        endTime: $end,
                    ),
                ),
            )
            ->willReturn($createdPeriod);

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
        );

        $command = new SavePeriodCommand();
        $command->measure = $measure;
        $command->startTime = $start;
        $command->endTime = $end;

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
            ->with(true, [ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::WEDNESDAY->value], $start, $end);

        $this->periodRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SavePeriodCommandHandler(
            $this->idFactory,
            $this->periodRepository,
        );

        $command = new SavePeriodCommand($period);
        $command->startTime = $start;
        $command->endTime = $end;

        $result = $handler($command);

        $this->assertSame($period, $result);
    }
}
