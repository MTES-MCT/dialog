<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Regulation\Repository\DailyRangeRepositoryInterface;

final class SaveDailyRangeCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private DailyRangeRepositoryInterface $dailyRangeRepository,
    ) {
    }

    public function __invoke(SaveDailyRangeCommand $command): DailyRange
    {
        $command->sortApplicableDays();

        if ($command->dailyRange) {
            $command->dailyRange->update($command->applicableDays);

            return $command->dailyRange;
        }

        $dailyRange = $this->dailyRangeRepository->add(
            new DailyRange(
                uuid: $this->idFactory->make(),
                applicableDays: $command->applicableDays,
                period: $command->period,
            ),
        );

        return $dailyRange;
    }
}
