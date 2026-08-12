<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\Exception\GeocodingFailureException;
use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\Zone;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;
use App\Domain\Regulation\Repository\NumberedRoadRepositoryInterface;
use App\Domain\Regulation\Repository\RawGeoJSONRepositoryInterface;
use App\Domain\Regulation\Repository\ZoneRepositoryInterface;

final class ConvertPolygonsToZonesCommandHandler
{
    public function __construct(
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly ZoneRepositoryInterface $zoneRepository,
        private readonly RawGeoJSONRepositoryInterface $rawGeoJSONRepository,
        private readonly NamedStreetRepositoryInterface $namedStreetRepository,
        private readonly NumberedRoadRepositoryInterface $numberedRoadRepository,
        private readonly IdFactoryInterface $idFactory,
        private readonly RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(ConvertPolygonsToZonesCommand $command): ConvertPolygonsToZonesCommandResult
    {
        $locations = $this->locationRepository->findAllWithPolygonGeometry();

        $numLocations = \count($locations);
        $convertedLocationUuids = [];
        $exceptions = [];

        foreach ($locations as $location) {
            if ($command->dryRun) {
                $convertedLocationUuids[] = $location->getUuid();
                continue;
            }

            $polygon = $location->getGeometry();

            try {
                // Même calcul que pour une zone créée depuis le formulaire
                // (cf. GetZoneGeometryQueryHandler).
                $sections = $this->roadGeocoder->findSectionsInArea(
                    $polygon,
                    excludeTypes: [$this->roadGeocoder::HIGHWAY],
                    clipToArea: true,
                );
            } catch (GeocodingFailureException $exc) {
                $exceptions[$location->getUuid()] = $exc;
                continue;
            }

            $zone = $this->zoneRepository->add(
                new Zone(
                    uuid: $this->idFactory->make(),
                    location: $location,
                    label: $this->resolveZoneLabel($location),
                    geometry: $polygon,
                ),
            );
            $location->setZone($zone);
            $location->update(RoadTypeEnum::ZONE->value, $sections);
            $this->deleteRoadSubEntities($location);

            $convertedLocationUuids[] = $location->getUuid();
        }

        return new ConvertPolygonsToZonesCommandResult($numLocations, $convertedLocationUuids, $exceptions);
    }

    private function resolveZoneLabel(Location $location): string
    {
        if ($rawGeoJSON = $location->getRawGeoJSON()) {
            return $rawGeoJSON->getLabel();
        }

        if ($namedStreet = $location->getNamedStreet()) {
            return $namedStreet->getRoadName() ?? $namedStreet->getCityLabel() ?? 'Zone';
        }

        if ($numberedRoad = $location->getNumberedRoad()) {
            return $numberedRoad->getRoadNumber() ?? 'Zone';
        }

        return $location->getCityLabel() ?? 'Zone';
    }

    // Le type « zone » n'a pas d'autre sous-entité que Zone : on supprime celle de
    // l'ancien type de voie pour ne pas laisser de données orphelines.
    private function deleteRoadSubEntities(Location $location): void
    {
        if ($rawGeoJSON = $location->getRawGeoJSON()) {
            $this->rawGeoJSONRepository->delete($rawGeoJSON);
        }

        if ($namedStreet = $location->getNamedStreet()) {
            $this->namedStreetRepository->delete($namedStreet);
        }

        if ($numberedRoad = $location->getNumberedRoad()) {
            $this->numberedRoadRepository->delete($numberedRoad);
        }

        if ($location->getStorageArea()) {
            $location->setStorageArea(null);
        }

        foreach ($location->getExceptions() as $exception) {
            $location->removeException($exception);
        }
    }
}
