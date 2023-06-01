<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Condition;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\Condition;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Repository\ConditionRepositoryInterface;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;

final class SavePeriodCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private PeriodRepositoryInterface $periodRepository,
        private ConditionRepositoryInterface $conditionRepository,
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

        $condition = $this->conditionRepository->add(
            new Condition(
                uuid: $this->idFactory->make(),
                negate: false,
                measure: $command->measure,
            ),
        );

        return $this->periodRepository->add(
            new Period(
                uuid: $this->idFactory->make(),
                condition: $condition,
                includeHolidays: $command->includeHolidays,
                applicableDays: $command->applicableDays,
                startTime: $command->startTime,
                endTime: $command->endTime,
            ),
        );
    }
}
