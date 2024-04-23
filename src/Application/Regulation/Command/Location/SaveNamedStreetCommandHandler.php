<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\LaneSectionMakerInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;

final class SaveNamedStreetCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationRepository,
        private NamedStreetRepositoryInterface $namedStreetRepository,
        private RoadGeocoderInterface $roadGeocoder,
        private LaneSectionMakerInterface $laneSectionMaker,
    ) {
    }

    public function __invoke(SaveNamedStreetCommand $command): Location
    {
        $command->clean();

        if (!$command->namedStreet instanceof NamedStreet) {
            $geometry = $command->geometry ?? $this->computeGeometry($command);
            $location = $this->locationRepository->add(
                new Location(
                    uuid: $this->idFactory->make(),
                    measure: $command->measure,
                    roadType: RoadTypeEnum::LANE->value,
                    geometry: $geometry,
                ),
            );

            $namedStreet = $this->namedStreetRepository->add(
                new NamedStreet(
                    uuid: $this->idFactory->make(),
                    location: $location,
                    cityLabel: $command->cityLabel,
                    cityCode: $command->cityCode,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    toHouseNumber: $command->toHouseNumber,
                ),
            );

            $command->measure->addLocation($location);
            $location->setNamedStreet($namedStreet);

            return $location;
        }

        $location = $command->namedStreet->getLocation();
        $geometry = $this->shouldRecomputeGeometry($command) ? $this->computeGeometry($command) : $location->getGeometry();
        $command->namedStreet->update(
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            toHouseNumber: $command->toHouseNumber,
        );
        $location->updateGeometry($geometry);

        return $location;
    }

    private function computeGeometry(SaveNamedStreetCommand $command): ?string
    {
        $hasNoStart = !$command->fromCoords && !$command->fromHouseNumber && !$command->fromRoadName;
        $hasNoEnd = !$command->toCoords && !$command->toHouseNumber && !$command->toRoadName;

        if ($hasNoStart xor $hasNoEnd) {
            // Not supported yet.
            return null;
        }

        $fullLaneGeometry = $this->roadGeocoder->computeRoadLine($command->roadName, $command->cityCode);

        if ($hasNoStart && $hasNoEnd) {
            return $fullLaneGeometry;
        }

        return $this->laneSectionMaker->computeSection(
            $fullLaneGeometry,
            $command->roadName,
            $command->cityCode,
            $command->fromCoords,
            $command->fromHouseNumber,
            $command->fromRoadName,
            $command->toCoords,
            $command->toHouseNumber,
            $command->toRoadName,
        );
    }

    private function shouldRecomputeGeometry(SaveNamedStreetCommand $command): bool
    {
        return $command->cityCode !== $command->namedStreet->getCityCode()
            || $command->roadName !== $command->namedStreet->getRoadName()
            || ($command->fromHouseNumber !== $command->namedStreet->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->namedStreet->getToHouseNumber());
    }
}
