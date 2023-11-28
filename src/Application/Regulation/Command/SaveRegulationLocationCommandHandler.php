<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeometryFormatter;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\LocationAddress;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveRegulationLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private CommandBusInterface $commandBus,
        private LocationRepositoryInterface $locationRepository,
        private GeocoderInterface $geocoder,
        private GeometryFormatter $geometryFormatter,
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
                    address: $command->address,
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
            address: $command->address,
            fromHouseNumber: $command->fromHouseNumber,
            toHouseNumber: $command->toHouseNumber,
            geometry: $geometry,
        );

        return $command->location;
    }

    private function computeLine(string $address, string $fromHouseNumber, string $toHouseNumber): string
    {
        $fromHouseAddress = sprintf('%s %s', $fromHouseNumber, $address);
        $toHouseAddress = sprintf('%s %s', $toHouseNumber, $address);

        $fromCoords = $this->geocoder->computeCoordinates($fromHouseAddress);
        $toCoords = $this->geocoder->computeCoordinates($toHouseAddress);

        return $this->geometryFormatter->formatLine([$fromCoords, $toCoords]);
    }

    private function computeRoadLine(string $roadName, string $inseeCode): string
    {
        // Appeler l'API IGN

        $points = [Coordinates::fromLonLat(2.5634, 43.141), Coordinates::fromLonLat(2.14534, 43.265)];

        return $this->geometryFormatter->formatLine($points);
    }

    private function computeGeometry(SaveRegulationLocationCommand $command): ?string
    {
        if ($command->fromHouseNumber && $command->toHouseNumber) {
            return $this->computeLine($command->address, $command->fromHouseNumber, $command->toHouseNumber);
        }

        $address = LocationAddress::fromString($command->address);

        if (!$command->fromHouseNumber && !$command->toHouseNumber && $address->getRoadName()) {
            $inseeCode = '78396'; // obtenir à partir de postCode et city

            return $this->computeRoadLine($address->getRoadName(), $inseeCode);
        }

        return null;
    }

    private function shouldRecomputeGeometry(SaveRegulationLocationCommand $command): bool
    {
        return $command->address !== $command->location->getAddress()
            || ($command->fromHouseNumber !== $command->location->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->location->getToHouseNumber());
    }
}
