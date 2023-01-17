<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\GeocoderInterface;
use App\Application\GeographyFormatterInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Condition\Location;
use App\Domain\Condition\Repository\LocationRepositoryInterface;

final class SaveRegulationStep2CommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationRepository,
        private GeocoderInterface $geocoder,
        private GeographyFormatterInterface $geographyFormatter,
    ) {
    }

    private function computePoint(string $postalCode, string $city, string $roadName, string $houseNumber): string
    {
        $coords = $this->geocoder->computeCoordinates($postalCode, $city, $roadName, $houseNumber);

        return $this->geographyFormatter->formatPoint($coords->getLatitude(), $coords->getLongitude());
    }

    public function __invoke(SaveRegulationStep2Command $command): void
    {
        $regulationCondition = $command->regulationOrderRecord->getRegulationOrder()->getRegulationCondition();
        $command->regulationOrderRecord->updateLastFilledStep(2);

        // If submitting step 2 for the first time, we create the location
        if (!$command->location instanceof Location) {
            $fromPoint = $this->computePoint($command->postalCode, $command->city, $command->roadName, $command->fromHouseNumber);
            $toPoint = $this->computePoint($command->postalCode, $command->city, $command->roadName, $command->toHouseNumber);

            $this->locationRepository->save(
                new Location(
                    uuid: $this->idFactory->make(),
                    regulationCondition: $regulationCondition,
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
            $fromPoint = $this->computePoint($command->postalCode, $command->city, $command->roadName, $command->fromHouseNumber);
        } else {
            $fromPoint = $command->location->getFromPoint();
        }

        $toPointNeedsUpdating = $hasRoadChanged || ($command->toHouseNumber !== $command->location->getToHouseNumber());

        if ($toPointNeedsUpdating) {
            $toPoint = $this->computePoint($command->postalCode, $command->city, $command->roadName, $command->toHouseNumber);
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
