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

    public function __invoke(SaveRegulationLocationCommand $command): string
    {
        $regulationOrder = $command->regulationOrderRecord->getRegulationOrder();

        // Create location if needed
        if (!$command->location instanceof Location) {
            $fromPoint = $command->fromHouseNumber ? $this->computePoint($command->address, $command->fromHouseNumber) : null;
            $toPoint = $command->toHouseNumber ? $this->computePoint($command->address, $command->toHouseNumber) : null;

            $location = $this->locationRepository->add(
                new Location(
                    uuid: $this->idFactory->make(),
                    regulationOrder: $regulationOrder,
                    address: $command->address,
                    fromHouseNumber: $command->fromHouseNumber,
                    fromPoint: $fromPoint,
                    toHouseNumber: $command->toHouseNumber,
                    toPoint: $toPoint,
                ),
            );

            foreach ($command->measures as $measureCommand) {
                $measureCommand->location = $location;
                $measure = $this->commandBus->handle($measureCommand);
                $location->addMeasure($measure);
            }

            return $location->getUuid();
        }

        [ $fromPoint, $toPoint ] = $this->computePoints($command);

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
            fromPoint: $fromPoint,
            toHouseNumber: $command->toHouseNumber,
            toPoint: $toPoint,
        );

        return $command->location->getUuid();
    }

    private function computePoint(string $address, string $houseNumber): string
    {
        $houseAddress = sprintf('%s %s', $houseNumber, $address);
        $coords = $this->geocoder->computeCoordinates($houseAddress);

        return $this->geometryFormatter->formatPoint($coords->latitude, $coords->longitude);
    }

    private function computePoints(SaveRegulationLocationCommand $command): array
    {
        $hasRoadChanged = $command->address !== $command->location->getAddress();
        $fromPointNeedsUpdating = $hasRoadChanged || ($command->fromHouseNumber !== $command->location->getFromHouseNumber());

        if ($fromPointNeedsUpdating) {
            $fromPoint = $command->fromHouseNumber ? $this->computePoint($command->address, $command->fromHouseNumber) : null;
        } else {
            $fromPoint = $command->location->getFromPoint();
        }

        $toPointNeedsUpdating = $hasRoadChanged || ($command->toHouseNumber !== $command->location->getToHouseNumber());

        if ($toPointNeedsUpdating) {
            $toPoint = $command->toHouseNumber ? $this->computePoint($command->address, $command->toHouseNumber) : null;
        } else {
            $toPoint = $command->location->getToPoint();
        }

        return [
            $fromPoint,
            $toPoint,
        ];
    }
}
