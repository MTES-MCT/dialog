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
                    direction: $command->direction,
                    cityLabel: $command->cityLabel,
                    cityCode: $command->cityCode,
                    roadBanId: $command->roadBanId,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    fromRoadName: $command->fromRoadName,
                    toHouseNumber: $command->toHouseNumber,
                    toRoadName: $command->toRoadName,
                ),
            );
            $command->location->setNamedStreet($namedStreet);

            return $namedStreet;
        }

        $command->namedStreet->update(
            direction: $command->direction,
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
            roadBanId: $command->roadBanId,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            fromRoadName: $command->fromRoadName,
            toHouseNumber: $command->toHouseNumber,
            toRoadName: $command->toRoadName,
        );

        return $command->namedStreet;
    }
}
