<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationNewRepository,
        private GeocoderInterface $geocoder,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();

        // Create locationNew if needed
        if (!$command->locationNew instanceof Location) {
            $geometry = empty($command->geometry) ? $this->computeGeometry($command) : $command->geometry;

            $locationNew = $this->locationNewRepository->add(
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

            $command->measure->addLocationNew($locationNew);

            return $locationNew;
        }

        $geometry = $this->shouldRecomputeGeometry($command)
            ? $this->computeGeometry($command)
            : $command->locationNew->getGeometry();

        $command->locationNew->update(
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

        return $command->locationNew;
    }

    private function computeGeometry(SaveLocationCommand $command): ?string
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

    private function shouldRecomputeGeometry(SaveLocationCommand $command): bool
    {
        return $command->cityCode !== $command->locationNew->getCityCode()
            || $command->roadName !== $command->locationNew->getRoadName()
            || ($command->fromHouseNumber !== $command->locationNew->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->locationNew->getToHouseNumber());
    }
}
