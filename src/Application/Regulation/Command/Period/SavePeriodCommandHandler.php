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
        $command->startDate = $this->dateUtils->mergeDateAndTime($command->startDate, $command->startTime);
        $command->endDate = $this->dateUtils->mergeDateAndTime($command->endDate, $command->endTime);

        if ($command->period) {
            $dailyRange = $command->period->getDailyRange();

            if ($command->dailyRange) {
                $command->dailyRange->period = $command->period;
                $this->commandBus->handle($command->dailyRange);
            } elseif ($dailyRange) {
                $this->dailyRangeRepository->delete($dailyRange);
                $command->period->setDailyRange(null);
            }

            $command->period->update(
                startDateTime: $command->startDate,
                endDateTime: $command->endDate,
                recurrenceType: $command->recurrenceType,
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

        if ($command->dailyRange) {
            $command->dailyRange->period = $period;
            $dailyRange = $this->commandBus->handle($command->dailyRange);
            $period->setDailyRange($dailyRange);
        }

        return $period;
    }
}
