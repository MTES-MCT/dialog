<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Specification\CanOrganizationInterveneOnGeometry;

final class SaveLocationCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private LocationRepositoryInterface $locationRepository,
        private IdFactoryInterface $idFactory,
        private CanOrganizationInterveneOnGeometry $canOrganizationInterveneOnGeometry,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();
        $roadCommand = $command->getRoadCommand();
        $roadCommand->clean();
        $organizationUuid = $command->organization->getUuid();

        // Update location

        if ($location = $command->location) {
            $roadCommand->setLocation($location);
            $geometry = $this->queryBus->handle($roadCommand->getGeometryQuery());

            if (!$this->canOrganizationInterveneOnGeometry->isSatisfiedBy($organizationUuid, $geometry)) {
                throw new OrganizationCannotInterveneOnGeometryException();
            }

            $location->update($command->roadType, $geometry);
            $this->commandBus->handle($roadCommand);

            if ($deleteCommand = $command->getRoadDeleteCommand()) {
                $this->commandBus->handle($deleteCommand);
            }

            return $location;
        }

        // Create location

        $geometry = $this->queryBus->handle($roadCommand->getGeometryQuery());

        if (!$this->canOrganizationInterveneOnGeometry->isSatisfiedBy($organizationUuid, $geometry)) {
            throw new OrganizationCannotInterveneOnGeometryException();
        }

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
