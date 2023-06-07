<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Condition;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;

final class SavePeriodCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private PeriodRepositoryInterface $periodRepository,
    ) {
    }

    public function __invoke(SavePeriodCommand $command): Period
    {
        if ($command->period) {
            $command->period->update(
                includeHolidays: $command->includeHolidays,
                applicableDays: $command->applicableDays,
                startTime: $command->startTime,
                endTime: $command->endTime,
            );

            return $command->period;
        }

        return $this->periodRepository->add(
            new Period(
                uuid: $this->idFactory->make(),
                measure: $command->measure,
                includeHolidays: $command->includeHolidays,
                applicableDays: $command->applicableDays,
                startTime: $command->startTime,
                endTime: $command->endTime,
            ),
        );
    }
}
