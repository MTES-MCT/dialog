<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Repository\TimeSlotRepositoryInterface;

final class SaveTimeSlotCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private TimeSlotRepositoryInterface $timeSlotRepository,
    ) {
    }

    public function __invoke(SaveTimeSlotCommand $command): TimeSlot
    {
        if ($command->timeSlot) {
            $command->timeSlot->update(
                startTime: $command->startTime,
                endTime: $command->endTime,
            );

            return $command->timeSlot;
        }

        return $this->timeSlotRepository->add(
            new TimeSlot(
                uuid: $this->idFactory->make(),
                startTime: $command->startTime,
                endTime: $command->endTime,
                dailyRange: $command->dailyRange,
            ),
        );
    }
}
