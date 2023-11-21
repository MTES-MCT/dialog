<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommandHandler;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Repository\TimeSlotRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveTimeSlotCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $timeSlotRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->timeSlotRepository = $this->createMock(TimeSlotRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $startTime = new \DateTimeImmutable('2023-05-22 10:00:00');
        $endTime = new \DateTimeImmutable('2023-05-23 16:00:00');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('7fb74c5d-069b-4027-b994-7545bb0942d0');

        $createdTimeSlot = $this->createMock(TimeSlot::class);
        $period = $this->createMock(Period::class);

        $this->timeSlotRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new TimeSlot(
                        uuid: '7fb74c5d-069b-4027-b994-7545bb0942d0',
                        period: $period,
                        startTime: $startTime,
                        endTime: $endTime,
                    ),
                ),
            )
            ->willReturn($createdTimeSlot);

        $handler = new SaveTimeSlotCommandHandler(
            $this->idFactory,
            $this->timeSlotRepository,
        );

        $command = new SaveTimeSlotCommand();
        $command->startTime = $startTime;
        $command->endTime = $endTime;
        $command->period = $period;

        $result = $handler($command);

        $this->assertSame($createdTimeSlot, $result);
    }

    public function testUpdate(): void
    {
        $startTime = new \DateTimeImmutable('2023-05-22 10:00:00');
        $endTime = new \DateTimeImmutable('2023-05-23 16:00:00');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $timeSlot = $this->createMock(TimeSlot::class);
        $period = $this->createMock(Period::class);

        $this->timeSlotRepository
            ->expects(self::never())
            ->method('add');

        $timeSlot
            ->expects(self::once())
            ->method('update')
            ->with($startTime, $endTime);

        $handler = new SaveTimeSlotCommandHandler(
            $this->idFactory,
            $this->timeSlotRepository,
        );

        $command = new SaveTimeSlotCommand($timeSlot);
        $command->period = $period;
        $command->startTime = $startTime;
        $command->endTime = $endTime;

        $result = $handler($command);

        $this->assertSame($timeSlot, $result);
    }
}
