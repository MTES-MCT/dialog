<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;

final class SavePeriodCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private PeriodRepositoryInterface $periodRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(SavePeriodCommand $command): Period
    {
        $command->sortApplicableDays();
        $command->clear();
        $command->startDate = $this->dateUtils->mergeDateAndTimeOfTwoDates($command->startDate, $command->startHour);
        $command->endDate = $this->dateUtils->mergeDateAndTimeOfTwoDates($command->endDate, $command->endHour);

        if ($command->period) {
            $command->period->update(
                applicableDays: $command->applicableDays,
                startTime: $command->startDate, // todo : to remove
                endTime: $command->endDate, // todo : to remove
                startDate: $command->startDate,
                endDate: $command->endDate,
                recurrenceType: $command->recurrenceType,
            );

            return $command->period;
        }

        return $this->periodRepository->add(
            new Period(
                uuid: $this->idFactory->make(),
                measure: $command->measure,
                applicableDays: $command->applicableDays,
                startDate: $command->startDate,
                endDate: $command->endDate,
                startTime: $command->startDate, // todo : to remove
                endTime: $command->endDate, // todo : to remove
                recurrenceType: $command->recurrenceType,
            ),
        );
    }
}
