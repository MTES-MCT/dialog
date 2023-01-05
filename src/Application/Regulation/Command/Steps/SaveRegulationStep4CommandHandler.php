<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\Repository\VehicleCharacteristicsRepositoryInterface;
use App\Domain\Condition\VehicleCharacteristics;

final class SaveRegulationStep4CommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private VehicleCharacteristicsRepositoryInterface $vehicleCharacteristicsRepository,
    ) {
    }

    public function __invoke(SaveRegulationStep4Command $command): void
    {
        $regulationCondition = $command->regulationOrderRecord->getRegulationOrder()->getRegulationCondition();
        $command->regulationOrderRecord->updateLastFilledStep(4);

        if (!$command->maxWeight && !$command->maxHeight && !$command->maxWidth && !$command->maxLength) {
            return;
        }

        if (!$command->vehicleCharacteristics instanceof VehicleCharacteristics) {
            $this->vehicleCharacteristicsRepository->save(
                new VehicleCharacteristics(
                    uuid: $this->idFactory->make(),
                    regulationCondition: $regulationCondition,
                    maxWeight: $command->maxWeight,
                    maxHeight: $command->maxHeight,
                    maxWidth: $command->maxWidth,
                    maxLength: $command->maxLength,
                ),
            );

            return;
        }

        $command->vehicleCharacteristics->update(
            maxWeight: $command->maxWeight,
            maxHeight: $command->maxHeight,
            maxWidth: $command->maxWidth,
            maxLength: $command->maxLength,
        );
    }
}
