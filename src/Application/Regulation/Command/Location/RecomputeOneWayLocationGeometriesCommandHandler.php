<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

/**
 * Recalcule la géométrie des emprises à sens unique pour qu'elle soit orientée du point A vers le point B.
 *
 * Les emprises enregistrées avant l'orientation des sections (cf. LineSectionMaker) suivent le sens des tronçons
 * BD TOPO, qui ne dit rien du sens de circulation. On repasse par le calcul standard, celui d'une modification
 * depuis le formulaire : géocodage de A et B, découpage, orientation.
 */
final class RecomputeOneWayLocationGeometriesCommandHandler
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(RecomputeOneWayLocationGeometriesCommand $command): RecomputeOneWayLocationGeometriesCommandResult
    {
        $locations = $this->locationRepository->findAllOneWaySections();

        $numLocations = \count($locations);
        $updatedLocationUuids = [];
        $unchangedLocationUuids = [];
        $exceptions = [];

        foreach ($locations as $location) {
            $geometryQuery = (new SaveLocationCommand($location))->getRoadCommand()->getGeometryQuery();

            try {
                $geometry = $this->queryBus->handle($geometryQuery);
            } catch (GeocodingFailureException $exc) {
                $exceptions[$location->getUuid()] = $exc;
                continue;
            }

            if ($geometry === $location->getGeometry()) {
                $unchangedLocationUuids[] = $location->getUuid();
                continue;
            }

            if (!$command->dryRun) {
                $location->update($location->getRoadType(), $geometry);
            }

            $updatedLocationUuids[] = $location->getUuid();
        }

        return new RecomputeOneWayLocationGeometriesCommandResult($numLocations, $updatedLocationUuids, $unchangedLocationUuids, $exceptions);
    }
}
