<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Geography\GeometryFormatter;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveRegulationStep2CommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationRepository,
        private GeocoderInterface $geocoder,
        private GeometryFormatter $geometryFormatter,
    ) {
    }

    private function computePoint(string $address, string $houseNumber): string
    {
        $houseAddress = sprintf('%s %s', $houseNumber, $address);
        $coords = $this->geocoder->computeCoordinates($houseAddress);

        return $this->geometryFormatter->formatPoint($coords->latitude, $coords->longitude);
    }

    public function __invoke(SaveRegulationStep2Command $command): void
    {
        $regulationOrder = $command->regulationOrderRecord->getRegulationOrder();

        // If submitting step 2 for the first time, we create the location
        if (!$command->location instanceof Location) {
            $fromPoint = $command->fromHouseNumber ? $this->computePoint($command->address, $command->fromHouseNumber) : null;
            $toPoint = $command->toHouseNumber ? $this->computePoint($command->address, $command->toHouseNumber) : null;

            $this->locationRepository->save(
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

            return;
        }

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

        $command->location->update(
            address: $command->address,
            fromHouseNumber: $command->fromHouseNumber,
            fromPoint: $fromPoint,
            toHouseNumber: $command->toHouseNumber,
            toPoint: $toPoint,
        );
    }
}
