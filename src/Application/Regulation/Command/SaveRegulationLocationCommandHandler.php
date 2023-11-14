<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Geography\GeometryFormatter;
use App\Domain\Regulation\Location;
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
            if ($command->geometry) {
                $geometry = $command->geometry;
            } elseif ($command->fromHouseNumber && $command->toHouseNumber) {
                $geometry = $this->computeLine($command->address, $command->fromHouseNumber, $command->toHouseNumber);
            } else {
                $geometry = null;
            }

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

        $geometry = $this->computeGeometry($command);

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

        return $this->geometryFormatter->formatLine(
            $fromCoords->latitude,
            $fromCoords->longitude,
            $toCoords->latitude,
            $toCoords->longitude,
        );
    }

    private function computeGeometry(SaveRegulationLocationCommand $command): ?string
    {
        $geometryNeedsUpdating = $command->address !== $command->location->getAddress()
            || ($command->fromHouseNumber !== $command->location->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->location->getToHouseNumber());

        if ($geometryNeedsUpdating) {
            $geometry = ($command->fromHouseNumber && $command->toHouseNumber)
                ? $this->computeLine($command->address, $command->fromHouseNumber, $command->toHouseNumber)
                : null;
        } else {
            $geometry = $command->location->getGeometry();
        }

        return $geometry;
    }
}
