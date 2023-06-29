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
        $command->cleanTypes();

        if ($command->vehicleSet) {
            $command->vehicleSet->update(
                $command->restrictedTypes,
                $command->otherRestrictedTypeText,
                $command->exemptedTypes,
                $command->otherExemptedTypeText,
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
            ),
        );
    }
}