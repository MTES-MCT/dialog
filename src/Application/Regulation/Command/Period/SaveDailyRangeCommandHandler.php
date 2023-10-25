<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Regulation\Repository\DailyRangeRepositoryInterface;

final class SaveDailyRangeCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private DailyRangeRepositoryInterface $dailyRangeRepository,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(SaveDailyRangeCommand $command): DailyRange
    {
        $command->sortApplicableDays();

        if ($command->dailyRange) {
            $command->dailyRange->update($command->applicableDays);
            $timeSlotsStillPresentUuids = [];

            // TimeSlots provided with the command get created or updated...
            foreach ($command->timeSlots as $timeSlotCommand) {
                if ($timeSlotCommand->timeSlot) {
                    $timeSlotsStillPresentUuids[] = $timeSlotCommand->timeSlot->getUuid();
                }

                $timeSlotCommand->dailyRange = $command->dailyRange;
                $this->commandBus->handle($timeSlotCommand);
            }

            // TimeSlots that were not present in the command get deleted.
            foreach ($command->dailyRange->getTimeSlots() as $timeSlot) {
                if (!\in_array($timeSlot->getUuid(), $timeSlotsStillPresentUuids)) {
                    $this->commandBus->handle(new DeleteTimeSlotCommand($timeSlot));
                    $command->dailyRange->removeTimeSlot($timeSlot);
                }
            }

            return $command->dailyRange;
        }

        $dailyRange = $this->dailyRangeRepository->add(
            new DailyRange(
                uuid: $this->idFactory->make(),
                applicableDays: $command->applicableDays,
                period: $command->period,
            ),
        );

        foreach ($command->timeSlots as $timeSlotCommand) {
            $timeSlotCommand->dailyRange = $dailyRange;
            $timeSlot = $this->commandBus->handle($timeSlotCommand);
            $dailyRange->addTimeSlot($timeSlot);
        }

        return $dailyRange;
    }
}
