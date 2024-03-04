<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\GeocoderInterface;
use App\Application\GeometryServiceInterface;
use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationRepository,
        private GeocoderInterface $geocoder,
        private RoadGeocoderInterface $roadGeocoder,
        private GeometryServiceInterface $geometryService,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();

        // Create location if needed
        if (!$command->location instanceof Location) {
            $geometry = empty($command->geometry) ? $this->computeGeometry($command) : $command->geometry;

            $location = $this->locationRepository->add(
                new Location(
                    uuid: $this->idFactory->make(),
                    measure: $command->measure,
                    roadType: $command->roadType,
                    administrator: $command->administrator,
                    roadNumber: $command->roadNumber,
                    cityLabel: $command->cityLabel,
                    cityCode: $command->cityCode,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    toHouseNumber: $command->toHouseNumber,
                    geometry: $geometry,
                ),
            );

            $command->measure->addLocation($location);

            return $location;
        }

        $geometry = $this->shouldRecomputeGeometry($command)
            ? $this->computeGeometry($command)
            : $command->location->getGeometry();

        $command->location->update(
            roadType: $command->roadType,
            administrator: $command->administrator,
            roadNumber: $command->roadNumber,
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            toHouseNumber: $command->toHouseNumber,
            geometry: $geometry,
        );

        return $command->location;
    }

    private function computeGeometry(SaveLocationCommand $command): string
    {
        $roadLine = $this->roadGeocoder->computeRoadLine($command->roadName, $command->cityCode);

        $geometry = $roadLine;
        $startCoords = null;
        $endCoords = null;

        if ($command->fromHouseNumber) {
            $fromAddress = sprintf('%s %s', $command->fromHouseNumber, $command->roadName);
            $fromCoords = $this->geocoder->computeCoordinates($fromAddress, $command->cityCode);
            $startCoords = $this->geometryService->locatePointOnLine($fromCoords, $roadLine);
        }

        if ($command->toHouseNumber) {
            $toAddress = sprintf('%s %s', $command->toHouseNumber, $command->roadName);
            $toCoords = $this->geocoder->computeCoordinates($toAddress, $command->cityCode);
            $endCoords = $this->geometryService->locatePointOnLine($toCoords, $roadLine);
        }

        return $this->geometryService->clipLine($geometry, $startCoords, $endCoords);
    }

    private function shouldRecomputeGeometry(SaveLocationCommand $command): bool
    {
        return $command->cityCode !== $command->location->getCityCode()
            || $command->roadName !== $command->location->getRoadName()
            || ($command->fromHouseNumber !== $command->location->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->location->getToHouseNumber());
    }
}
