<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveLocationCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private LocationRepositoryInterface $locationRepository,
        private IdFactoryInterface $idFactory,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();
        $roadCommand = $command->getRoadCommand();
        $geometry = $this->queryBus->handle($roadCommand->getGeometryQuery());

        // Update location

        if ($location = $command->location) {
            $roadCommand->setLocation($location);
            $location->update($command->roadType, $geometry);
            $this->commandBus->handle($roadCommand);

            if ($deleteCommand = $command->getRoadDeleteCommand()) {
                $this->commandBus->handle($deleteCommand);
            }

            return $location;
        }

        // Create location

        $location = $this->locationRepository->add(
            new Location(
                uuid: $this->idFactory->make(),
                measure: $command->measure,
                roadType: $command->roadType,
                geometry: $geometry,
            ),
        );
        $roadCommand->setLocation($location);
        $this->commandBus->handle($roadCommand);
        $command->measure->addLocation($location);

        return $location;
    }
}
