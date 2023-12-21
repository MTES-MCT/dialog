<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Country\France\Repository\CityRepositoryInterface;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveRegulationLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private CommandBusInterface $commandBus,
        private LocationRepositoryInterface $locationRepository,
        private GeocoderInterface $geocoder,
        private RoadGeocoderInterface $roadGeocoder,
        private CityRepositoryInterface $cityRepository,
    ) {
    }

    public function __invoke(SaveRegulationLocationCommand $command): Location
    {
        $regulationOrder = $command->regulationOrderRecord->getRegulationOrder();

        // Create location if needed
        if (!$command->location instanceof Location) {
            $geometry = empty($command->geometry) ? $this->computeGeometry($command) : $command->geometry;

            $location = $this->locationRepository->add(
                new Location(
                    uuid: $this->idFactory->make(),
                    regulationOrder: $regulationOrder,
                    cityLabel: $command->cityLabel,
                    cityCode: $command->cityCode,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    toHouseNumber: $command->toHouseNumber,
                    geometry: $geometry,
                ),
            );

            foreach ($command->measures as $measureCommand) {
                $measureCommand->location = $location;
                $measure = $this->commandBus->handle($measureCommand);
                $location->addMeasure($measure);
            }

            $regulationOrder->addLocation($location);

            return $location;
        }

        $geometry = $this->shouldRecomputeGeometry($command)
            ? $this->computeGeometry($command)
            : $command->location->getGeometry();

        $measuresStillPresentUuids = [];

        // Measures provided with the command get created or updated...
        foreach ($command->measures as $measureCommand) {
            if ($measureCommand->measure) {
                $measuresStillPresentUuids[] = $measureCommand->measure->getUuid();
            }

            $measureCommand->location = $command->location;
            $this->commandBus->handle($measureCommand);
        }

        // Measures that weren't present in the command get deleted.
        foreach ($command->location->getMeasures() as $measure) {
            if (!\in_array($measure->getUuid(), $measuresStillPresentUuids)) {
                $command->location->removeMeasure($measure);
                $this->commandBus->handle(new DeleteMeasureCommand($measure));
            }
        }

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
        $dptmt = substr($cityCode, 0, 2);

        $name = $command->cityLabel;
        $nameWithoutPostCode = explode(' ', $name);
        unset($nameWithoutPostCode[1]);
        $nameWithoutPostCode = implode(' ', $nameWithoutPostCode);

        if (!$command->fromHouseNumber && !$command->toHouseNumber && $roadName) {
            $city = $this->cityRepository->findOneByNameAndDepartement($nameWithoutPostCode, $dptmt);

            return $this->roadGeocoder->computeRoadLine($roadName, $city->getInseeCode());
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
