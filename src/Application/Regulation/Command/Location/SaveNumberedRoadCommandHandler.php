<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Repository\NumberedRoadRepositoryInterface;

final class SaveNumberedRoadCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationRepository,
        private NumberedRoadRepositoryInterface $numberedRoadRepository,
        private RoadGeocoderInterface $roadGeocoder,
        private RoadSectionMakerInterface $roadSectionMaker,
    ) {
    }

    public function __invoke(SaveNumberedRoadCommand $command): Location
    {
        if (!$command->numberedRoad instanceof NumberedRoad) {
            $geometry = $command->geometry ?? $this->computeGeometry($command);
            $location = $this->locationRepository->add(
                new Location(
                    uuid: $this->idFactory->make(),
                    measure: $command->measure,
                    roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                    geometry: $geometry,
                ),
            );

            $this->numberedRoadRepository->add(
                new NumberedRoad(
                    uuid: $this->idFactory->make(),
                    location: $location,
                    roadNumber: $command->roadNumber,
                    administrator: $command->administrator,
                    fromPointNumber: $command->fromPointNumber,
                    fromAbscissa: $command->fromAbscissa,
                    fromSide: $command->fromSide,
                    toPointNumber: $command->toPointNumber,
                    toAbscissa: $command->toAbscissa,
                    toSide: $command->toSide,
                ),
            );

            $command->measure->addLocation($location);

            return $location;
        }

        $location = $command->numberedRoad->getLocation();
        $geometry = $this->shouldRecomputeGeometry($command) ? $this->computeGeometry($command) : $location->getGeometry();
        $command->numberedRoad->update(
            administrator: $command->administrator,
            roadNumber: $command->roadNumber,
            fromPointNumber: $command->fromPointNumber,
            fromAbscissa: $command->fromAbscissa,
            fromSide: $command->fromSide,
            toPointNumber: $command->toPointNumber,
            toAbscissa: $command->toAbscissa,
            toSide: $command->toSide,
        );
        $location->updateGeometry($geometry);

        return $location;
    }

    private function computeGeometry(SaveNumberedRoadCommand $command): string
    {
        $fullDepartmentalRoadGeometry = $this->roadGeocoder->computeRoad($command->roadNumber, $command->administrator);

        return $this->roadSectionMaker->computeSection(
            $fullDepartmentalRoadGeometry,
            $command->administrator,
            $command->roadNumber,
            $command->fromPointNumber,
            $command->fromSide,
            $command->fromAbscissa ?? 0,
            $command->toPointNumber,
            $command->toSide,
            $command->toAbscissa ?? 0,
        );
    }

    private function shouldRecomputeGeometry(SaveNumberedRoadCommand $command): bool
    {
        return $command->roadNumber !== $command->numberedRoad->getRoadNumber()
            || $command->administrator !== $command->numberedRoad->getAdministrator()
            || $command->fromPointNumber !== $command->numberedRoad->getFromPointNumber()
            || $command->toPointNumber !== $command->numberedRoad->getToPointNumber()
            || $command->fromAbscissa !== $command->numberedRoad->getFromAbscissa()
            || $command->toAbscissa !== $command->numberedRoad->getToAbscissa()
            || $command->fromSide !== $command->numberedRoad->getFromSide()
            || $command->toSide !== $command->numberedRoad->getToSide();
    }
}
