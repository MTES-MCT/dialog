<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Repository\DailyRangeRepositoryInterface;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;

final class SavePeriodCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private PeriodRepositoryInterface $periodRepository,
        private DailyRangeRepositoryInterface $dailyRangeRepository,
        private DateUtilsInterface $dateUtils,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(SavePeriodCommand $command): Period
    {
        $command->clean();

        // In the case of a temporary order only
        if ($command->startTime) {
            $command->startDate = $this->dateUtils->mergeDateAndTime($command->startDate, $command->startTime);
        }

        $command->endDate = $command->endDate && $command->endTime
            ? $this->dateUtils->mergeDateAndTime($command->endDate, $command->endTime)
            : null;

        if ($command->period) {
            $dailyRange = $command->period->getDailyRange();

            if ($command->dailyRange) {
                $command->dailyRange->period = $command->period;
                $this->commandBus->handle($command->dailyRange);
            } elseif ($dailyRange) {
                $this->dailyRangeRepository->delete($dailyRange);
                $command->period->setDailyRange(null);
            }

            $timeSlotsStillPresentUuids = [];

            // TimeSlots provided with the command get created or updated...
            foreach ($command->timeSlots as $timeSlotCommand) {
                if ($timeSlotCommand->timeSlot) {
                    $timeSlotsStillPresentUuids[] = $timeSlotCommand->timeSlot->getUuid();
                }

                $timeSlotCommand->period = $command->period;
                $this->commandBus->handle($timeSlotCommand);
            }

            // TimeSlots that were not present in the command get deleted.
            foreach ($command->period->getTimeSlots() as $timeSlot) {
                if (!\in_array($timeSlot->getUuid(), $timeSlotsStillPresentUuids)) {
                    $this->commandBus->handle(new DeleteTimeSlotCommand($timeSlot));
                    $command->period->removeTimeSlot($timeSlot);
                }
            }

            $command->period->update(
                startDateTime: $command->startDate,
                recurrenceType: $command->recurrenceType,
                endDateTime: $command->endDate,
            );

            return $command->period;
        }

        $period = $this->periodRepository->add(
            new Period(
                uuid: $this->idFactory->make(),
                measure: $command->measure,
                startDateTime: $command->startDate,
                endDateTime: $command->endDate,
                recurrenceType: $command->recurrenceType,
            ),
        );

        foreach ($command->timeSlots as $timeSlotCommand) {
            $timeSlotCommand->period = $period;
            $timeSlot = $this->commandBus->handle($timeSlotCommand);
            $period->addTimeSlot($timeSlot);
        }

        if ($command->dailyRange) {
            $command->dailyRange->period = $period;
            $dailyRange = $this->commandBus->handle($command->dailyRange);
            $period->setDailyRange($dailyRange);
        }

        return $period;
    }
}
