<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\Regulation\Command\Period\DeleteTimeSlotCommand;
use App\Application\Regulation\Command\Period\DeleteTimeSlotCommandHandler;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Repository\TimeSlotRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteTimeSlotCommandHandlerTest extends TestCase
{
    private $timeslot;
    private $timeSlotRepository;

    protected function setUp(): void
    {
        $this->timeslot = $this->createMock(TimeSlot::class);
        $this->timeSlotRepository = $this->createMock(TimeSlotRepositoryInterface::class);
    }

    public function testDelete(): void
    {
        $this->timeSlotRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->timeslot));

        $handler = new DeleteTimeSlotCommandHandler($this->timeSlotRepository);
        $command = new DeleteTimeSlotCommand($this->timeslot);
        $this->assertEmpty($handler($command));
    }
}
