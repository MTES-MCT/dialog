<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\Exception\GeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationRepository,
        private GeocoderInterface $geocoder,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();

        // Create location if needed
        if (!$command->location instanceof Location) {
            $geometry = null;
            $departmentalRoadGeometry = null;

            if ($command->roadType === RoadTypeEnum::LANE->value) {
                $geometry = empty($command->geometry) ? $this->computeLaneGeometry($command) : $command->geometry;
            } else {
                $departmentalRoadGeometry = empty($command->departmentalRoadGeometry) ? $this->computeDepartmentalRoadGeometry($command) : $command->departmentalRoadGeometry;
            }

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
                    departmentalRoadGeometry: $departmentalRoadGeometry,
                ),
            );

            $command->measure->addLocation($location);

            return $location;
        }

        $geometry = null;
        $departmentalRoadGeometry = null;

        if ($command->roadType === RoadTypeEnum::LANE->value) {
            $geometry = $this->shouldRecomputeLaneGeometry($command)
                ? $this->computeLaneGeometry($command)
                : $command->location->getGeometry();
        } else {
            $departmentalRoadGeometry = $this->shouldRecomputeDepartmentalRoadGeometry($command)
                ? $this->computeDepartmentalRoadGeometry($command)
                : $command->location->getDepartmentalRoadGeometry();
        }

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
            departmentalRoadGeometry: $departmentalRoadGeometry,
        );

        return $command->location;
    }

    private function computeLaneGeometry(SaveLocationCommand $command): ?string
    {
        $hasBothEnds = (
            ($command->fromHouseNumber || $command->fromRoadName)
            && ($command->toHouseNumber || $command->toRoadName)
        );

        if ($hasBothEnds) {
            if ($command->fromHouseNumber) {
                $fromAddress = sprintf('%s %s', $command->fromHouseNumber, $command->roadName);
                $fromCoords = $this->geocoder->computeCoordinates($fromAddress, $command->cityCode);
            } else {
                $fromCoords = $this->geocoder->computeJunctionCoordinates($command->roadName, $command->fromRoadName, $command->cityCode);
            }

            if ($command->toHouseNumber) {
                $toAddress = sprintf('%s %s', $command->toHouseNumber, $command->roadName);
                $toCoords = $this->geocoder->computeCoordinates($toAddress, $command->cityCode);
            } else {
                $toCoords = $this->geocoder->computeJunctionCoordinates($command->roadName, $command->toRoadName, $command->cityCode);
            }

            return GeoJSON::toLineString([$fromCoords, $toCoords]);
        }

        $hasNoEnds = (
            !$command->fromHouseNumber
            && !$command->fromRoadName
            && !$command->toHouseNumber
            && !$command->toRoadName
        );

        if ($hasNoEnds && $command->roadName) {
            return $this->roadGeocoder->computeRoadLine($command->roadName, $command->cityCode);
        }

        return null;
    }

    private function computeDepartmentalRoadGeometry(SaveLocationCommand $command): ?string
    {
        if ($command->departmentalRoadGeometry) {
            return $command->departmentalRoadGeometry;
        }

        $departmentalRoadNumbers = $this->roadGeocoder->findDepartmentalRoads($command->roadNumber, $command->administrator);
        if (!$departmentalRoadNumbers) {
            throw new GeocodingFailureException(sprintf('could not retrieve geometry for roadNumber="%s", administrator="%s"', $command->roadNumber, $command->administrator));
        }

        return $departmentalRoadNumbers[0]['geometry'];
    }

    private function shouldRecomputeLaneGeometry(SaveLocationCommand $command): bool
    {
        return $command->cityCode !== $command->location->getCityCode()
            || $command->roadName !== $command->location->getRoadName()
            || ($command->fromHouseNumber !== $command->location->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->location->getToHouseNumber());
    }

    private function shouldRecomputeDepartmentalRoadGeometry(SaveLocationCommand $command): bool
    {
        return $command->departmentalRoadGeometry !== $command->location->getDepartmentalRoadGeometry()
            || $command->roadNumber !== $command->location->getRoadNumber()
            || $command->administrator !== $command->location->getAdministrator();
    }
}
