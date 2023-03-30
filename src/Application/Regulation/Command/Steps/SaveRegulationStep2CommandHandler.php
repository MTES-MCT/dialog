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

    private function computePoint(string $postalCode, string $city, string $roadName, string $houseNumber): string
    {
        $address = sprintf('%s %s %s %s', $houseNumber, $roadName, $postalCode, $city);
        $coords = $this->geocoder->computeCoordinates($address, postalCodeHint: $postalCode);

        return $this->geometryFormatter->formatPoint($coords->latitude, $coords->longitude);
    }

    public function __invoke(SaveRegulationStep2Command $command): void
    {
        $regulationOrder = $command->regulationOrderRecord->getRegulationOrder();

        // If submitting step 2 for the first time, we create the location
        if (!$command->location instanceof Location) {
            $fromPoint = $command->fromHouseNumber ? $this->computePoint($command->postalCode, $command->city, $command->roadName, $command->fromHouseNumber) : null;
            $toPoint = $command->toHouseNumber ? $this->computePoint($command->postalCode, $command->city, $command->roadName, $command->toHouseNumber) : null;

            $this->locationRepository->save(
                new Location(
                    uuid: $this->idFactory->make(),
                    regulationOrder: $regulationOrder,
                    postalCode: $command->postalCode,
                    city: $command->city,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    fromPoint: $fromPoint,
                    toHouseNumber: $command->toHouseNumber,
                    toPoint: $toPoint,
                ),
            );

            return;
        }

        $hasRoadChanged = (
            $command->postalCode !== $command->location->getPostalCode()
            || $command->city !== $command->location->getCity()
            || $command->roadName !== $command->location->getRoadName()
        );

        $fromPointNeedsUpdating = $hasRoadChanged || ($command->fromHouseNumber !== $command->location->getFromHouseNumber());

        if ($fromPointNeedsUpdating) {
            $fromPoint = $command->fromHouseNumber ? $this->computePoint($command->postalCode, $command->city, $command->roadName, $command->fromHouseNumber) : null;
        } else {
            $fromPoint = $command->location->getFromPoint();
        }

        $toPointNeedsUpdating = $hasRoadChanged || ($command->toHouseNumber !== $command->location->getToHouseNumber());

        if ($toPointNeedsUpdating) {
            $toPoint = $command->toHouseNumber ? $this->computePoint($command->postalCode, $command->city, $command->roadName, $command->toHouseNumber) : null;
        } else {
            $toPoint = $command->location->getToPoint();
        }

        $command->location->update(
            postalCode: $command->postalCode,
            city: $command->city,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            fromPoint: $fromPoint,
            toHouseNumber: $command->toHouseNumber,
            toPoint: $toPoint,
        );
    }
}
