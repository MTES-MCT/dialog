<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommandHandler;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Repository\DailyRangeRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveDailyRangeCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $dailyRangeRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->dailyRangeRepository = $this->createMock(DailyRangeRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('d035fec0-30f3-4134-95b9-d74c68eb53e3');

        $createdDailyRange = $this->createMock(DailyRange::class);
        $period = $this->createMock(Period::class);

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

        $handler = new SaveDailyRangeCommandHandler(
            $this->idFactory,
            $this->dailyRangeRepository,
        );

        $command = new SaveDailyRangeCommand();
        $command->period = $period;
        $command->applicableDays = [ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::WEDNESDAY->value];

        $result = $handler($command);

        $this->assertSame($createdDailyRange, $result);
    }

    public function testUpdate(): void
    {
        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->dailyRangeRepository
            ->expects(self::never())
            ->method('add');

        $period = $this->createMock(Period::class);
        $dailyRange = $this->createMock(DailyRange::class);
        $dailyRange
            ->expects(self::once())
            ->method('update')
            ->with([ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::WEDNESDAY->value]);

        $handler = new SaveDailyRangeCommandHandler(
            $this->idFactory,
            $this->dailyRangeRepository,
        );

        $command = new SaveDailyRangeCommand($dailyRange);
        $command->period = $period;
        $command->applicableDays = [ApplicableDayEnum::TUESDAY->value, ApplicableDayEnum::MONDAY->value, ApplicableDayEnum::WEDNESDAY->value];

        $result = $handler($command);

        $this->assertSame($dailyRange, $result);
    }
}
