<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class GeocodeLocationsWithoutGeometryCommandHandler
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(GeocodeLocationsWithoutGeometryCommand $command): GeocodeLocationsWithoutGeometryCommandResult
    {
        $locations = $this->locationRepository->findAllWithoutGeometry();

        $numLocations = \count($locations);
        $updatedLocationUuids = [];
        $exceptions = [];

        foreach ($locations as $location) {
            $locationCommand = new SaveLocationCommand($location);

            $geometryQuery = $locationCommand->getRoadCommand()->getGeometryQuery();

            try {
                $geometry = $this->queryBus->handle($geometryQuery);
                $location->update($location->getRoadType(), $geometry);
                $updatedLocationUuids[] = $location->getUuid();
            } catch (GeocodingFailureException $exc) {
                $exceptions[$location->getUuid()] = $exc;
            }
        }

        return new GeocodeLocationsWithoutGeometryCommandResult($numLocations, $updatedLocationUuids, $exceptions);
    }
}
