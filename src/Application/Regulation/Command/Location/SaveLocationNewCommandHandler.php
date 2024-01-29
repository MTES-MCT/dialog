<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Repository\LocationNewRepositoryInterface;

final class SaveLocationNewCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationNewRepositoryInterface $locationNewRepository,
    ) {
    }

    public function __invoke(SaveLocationNewCommand $command): LocationNew
    {
        // Create locationNew if needed
        if (!$command->locationNew instanceof LocationNew) {
            $locationNew = $this->locationNewRepository->add(
                new LocationNew(
                    uuid: $this->idFactory->make(),
                    measure: $command->measure,
                    cityLabel: $command->cityLabel,
                    cityCode: $command->cityCode,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    toHouseNumber: $command->toHouseNumber,
                    geometry: $command->geometry,
                ),
            );

            $command->measure->addLocationNew($locationNew);

            return $locationNew;
        }

        $command->locationNew->update(
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            toHouseNumber: $command->toHouseNumber,
            geometry: $command->geometry,
        );

        return $command->locationNew;
    }
}
