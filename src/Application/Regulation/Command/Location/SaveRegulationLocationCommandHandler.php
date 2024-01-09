<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Repository\LocationNewRepositoryInterface;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveRegulationLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private CommandBusInterface $commandBus,
        private LocationRepositoryInterface $locationRepository,
        private LocationNewRepositoryInterface $locationNewRepository,
        private GeocoderInterface $geocoder,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(SaveRegulationLocationCommand $command): LocationNew
    {
        $measure = $command->measure;

        // Create location if needed
        if (!$command->location instanceof LocationNew) {
            $geometry = empty($command->geometry) ? $this->computeGeometry($command) : $command->geometry;

            $locationNew = $this->locationNewRepository->add(
                new LocationNew(
                    uuid: $this->idFactory->make(),
                    measure: $measure,
                    cityLabel: $command->cityLabel,
                    cityCode: $command->cityCode,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    toHouseNumber: $command->toHouseNumber,
                    geometry: $geometry,
                ),
            );
            $measure->addLocation($locationNew);

            return $locationNew;
        }

        $geometry = $this->shouldRecomputeGeometry($command)
            ? $this->computeGeometry($command)
            : $command->location->getGeometry();

        $command->location->update(
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            toHouseNumber: $command->toHouseNumber,
            geometry: $geometry,
        );

        return $command->location;
    }

    private function computeGeometry(SaveRegulationLocationCommand $command): ?string
    {
        if ($command->fromHouseNumber && $command->toHouseNumber) {
            $fromAddress = sprintf('%s %s', $command->fromHouseNumber, $command->roadName);
            $toAddress = sprintf('%s %s', $command->toHouseNumber, $command->roadName);

            $fromCoords = $this->geocoder->computeCoordinates($fromAddress, $command->cityCode);
            $toCoords = $this->geocoder->computeCoordinates($toAddress, $command->cityCode);

            return GeoJSON::toLineString([$fromCoords, $toCoords]);
        }

        $roadName = $command->roadName;
        $cityCode = $command->cityCode;

        if (!$command->fromHouseNumber && !$command->toHouseNumber && $roadName) {
            return $this->roadGeocoder->computeRoadLine($roadName, $cityCode);
        }

        return null;
    }

    private function shouldRecomputeGeometry(SaveRegulationLocationCommand $command): bool
    {
        return $command->cityCode !== $command->location->getCityCode()
            || $command->roadName !== $command->location->getRoadName()
            || ($command->fromHouseNumber !== $command->location->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->location->getToHouseNumber());
    }
}
