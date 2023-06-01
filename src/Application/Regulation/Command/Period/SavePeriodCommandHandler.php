<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
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
        usort($command->applicableDays, function (string $d1, string $d2) {
            return ApplicableDayEnum::getDayIndex($d1) <=> ApplicableDayEnum::getDayIndex($d2);
        });

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
