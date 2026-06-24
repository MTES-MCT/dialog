<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\WholeCityException;
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
            $this->applyRoadCommand($roadCommand, $location);

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
        $this->applyRoadCommand($roadCommand, $location);
        $command->measure->addLocation($location);

        return $location;
    }

    private function applyRoadCommand(RoadCommandInterface $roadCommand, Location $location): void
    {
        // « Ville entière » n'a pas de sous-entité dédiée : ses données (ville + voies exclues)
        // vivent sur la localisation elle-même, donc rien à persister via un sous-handler.
        if ($roadCommand instanceof SaveWholeCityCommand) {
            $location->setWholeCity($roadCommand->cityCode, $roadCommand->cityLabel);
            $this->syncWholeCityExceptions($roadCommand, $location);

            return;
        }

        $this->commandBus->handle($roadCommand);
    }

    private function syncWholeCityExceptions(SaveWholeCityCommand $command, Location $location): void
    {
        // On remplace l'ensemble des exceptions (l'orphan removal supprime les anciennes).
        foreach ($location->getExceptions() as $existingException) {
            $location->removeException($existingException);
        }

        foreach ($command->exceptions as $exceptionCommand) {
            $geometryQuery = $exceptionCommand->getGeometryQuery();
            $geometry = $geometryQuery ? $this->queryBus->handle($geometryQuery) : null;

            $location->addException(
                new WholeCityException(
                    uuid: $this->idFactory->make(),
                    location: $location,
                    roadType: $exceptionCommand->roadType,
                    label: $exceptionCommand->getLabel(),
                    geometry: $geometry,
                    data: $exceptionCommand->toData(),
                ),
            );
        }
    }
}
