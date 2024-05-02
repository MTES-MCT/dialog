<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;

final class SaveNamedStreetCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private NamedStreetRepositoryInterface $namedStreetRepository,
    ) {
    }

    public function __invoke(SaveNamedStreetCommand $command): NamedStreet
    {
        $command->clean();

        if (!$command->namedStreet instanceof NamedStreet) {
            $namedStreet = $this->namedStreetRepository->add(
                new NamedStreet(
                    uuid: $this->idFactory->make(),
                    location: $command->location,
                    cityLabel: $command->cityLabel,
                    cityCode: $command->cityCode,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    toHouseNumber: $command->toHouseNumber,
                ),
            );
            $command->location->setNamedStreet($namedStreet);

            return $namedStreet;
        }

        $command->namedStreet->update(
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            toHouseNumber: $command->toHouseNumber,
        );

        return $command->namedStreet;
    }
}
