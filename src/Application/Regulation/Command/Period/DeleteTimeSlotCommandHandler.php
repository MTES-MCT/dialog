<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Domain\Regulation\Repository\TimeSlotRepositoryInterface;

final class DeleteTimeSlotCommandHandler
{
    public function __construct(
        private TimeSlotRepositoryInterface $timeSlotRepository,
    ) {
    }

    public function __invoke(DeleteTimeSlotCommand $command): void
    {
        $this->timeSlotRepository->delete($command->timeSlot);
    }
}
