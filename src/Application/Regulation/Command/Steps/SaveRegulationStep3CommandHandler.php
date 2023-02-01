<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\Period\Repository\OverallPeriodRepositoryInterface;

final class SaveRegulationStep3CommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private OverallPeriodRepositoryInterface $overallPeriodRepository,
    ) {
    }

    public function __invoke(SaveRegulationStep3Command $command): void
    {
        $regulationCondition = $command->regulationOrderRecord->getRegulationOrder()->getRegulationCondition();

        // If submitting step 3 for the first time, we create the overallPeriod
        if (!$command->overallPeriod instanceof OverallPeriod) {
            $this->overallPeriodRepository->save(
                new OverallPeriod(
                    uuid: $this->idFactory->make(),
                    regulationCondition: $regulationCondition,
                    startPeriod: $command->startPeriod,
                    endPeriod: $command->endPeriod,
                ),
            );

            return;
        }

        $command->overallPeriod->update($command->startPeriod, $command->endPeriod);
    }
}
