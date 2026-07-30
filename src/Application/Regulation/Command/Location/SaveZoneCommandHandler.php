<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\Location\Zone;
use App\Domain\Regulation\Repository\ZoneRepositoryInterface;

final class SaveZoneCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private ZoneRepositoryInterface $zoneRepository,
    ) {
    }

    public function __invoke(SaveZoneCommand $command): Zone
    {
        if (!$command->zone instanceof Zone) {
            $zone = $this->zoneRepository->add(
                new Zone(
                    uuid: $this->idFactory->make(),
                    location: $command->location,
                    label: $command->label,
                    geometry: $command->geometry,
                ),
            );

            $command->location->setZone($zone);

            return $zone;
        }

        $command->zone->update(
            label: $command->label,
            geometry: $command->geometry,
        );

        return $command->zone;
    }
}
