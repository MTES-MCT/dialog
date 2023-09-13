<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\VehicleSet;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Repository\VehicleSetRepositoryInterface;

final class SaveVehicleSetCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private VehicleSetRepositoryInterface $vehicleSetRepository,
    ) {
    }

    public function __invoke(SaveVehicleSetCommand $command): VehicleSet
    {
        $command->clean();

        if ($command->vehicleSet) {
            $command->vehicleSet->update(
                $command->restrictedTypes,
                $command->otherRestrictedTypeText,
                $command->exemptedTypes,
                $command->otherExemptedTypeText,
                $command->heavyweightMaxWeight,
                $command->heavyweightMaxWidth,
                $command->heavyweightMaxLength,
                $command->heavyweightMaxHeight,
                $command->critairTypes,
            );

            return $command->vehicleSet;
        }

        return $this->vehicleSetRepository->add(
            new VehicleSet(
                $this->idFactory->make(),
                $command->measure,
                $command->restrictedTypes,
                $command->otherRestrictedTypeText,
                $command->exemptedTypes,
                $command->otherExemptedTypeText,
                $command->heavyweightMaxWeight,
                $command->heavyweightMaxWidth,
                $command->heavyweightMaxLength,
                $command->heavyweightMaxHeight,
                $command->critairTypes,
            ),
        );
    }
}
