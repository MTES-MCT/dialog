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
        $regulationCondition = $command->regulationOrder->getRegulationCondition();

        $isEmpty = !$command->maxWeight && !$command->maxHeight && !$command->maxWidth && !$command->maxLength;
        if ($isEmpty) {
            return;
        }

        // If submitting step 4 for the first time, we create the vehicleCharacteristics
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
