<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\LaneSectionMakerInterface;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationRepository,
        private RoadGeocoderInterface $roadGeocoder,
        private LaneSectionMakerInterface $laneSectionMaker,
        private RoadSectionMakerInterface $roadSectionMaker,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();

        // Create location if needed
        if (!$command->location instanceof Location) {
            if ($command->roadType === RoadTypeEnum::LANE->value) {
                $geometry = empty($command->geometry) ? $this->computeLaneGeometry($command) : $command->geometry;
            } else {
                $geometry = $this->computeRoadSectionGeometry($command);
            }

            $location = $this->locationRepository->add(
                new Location(
                    uuid: $this->idFactory->make(),
                    measure: $command->measure,
                    roadType: $command->roadType,
                    roadNumber: $command->roadNumber,
                    cityLabel: $command->cityLabel,
                    cityCode: $command->cityCode,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    toHouseNumber: $command->toHouseNumber,
                    administrator: $command->administrator,
                    fromPointNumber: $command->fromPointNumber,
                    fromAbscissa: $command->fromAbscissa,
                    fromSide: $command->fromSide,
                    toPointNumber: $command->toPointNumber,
                    toAbscissa: $command->toAbscissa,
                    toSide: $command->toSide,
                    geometry: $geometry,
                ),
            );

            $command->measure->addLocation($location);

            return $location;
        }

        if ($command->roadType === RoadTypeEnum::LANE->value) {
            $geometry = $this->shouldRecomputeLaneGeometry($command)
                ? $this->computeLaneGeometry($command)
                : $command->location->getGeometry();
        } else {
            $geometry = $this->shouldRecomputeRoadSectionGeometry($command)
                ? $this->computeRoadSectionGeometry($command)
                : $command->location->getGeometry();
        }

        $command->location->update(
            roadType: $command->roadType,
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            toHouseNumber: $command->toHouseNumber,
            administrator: $command->administrator,
            roadNumber: $command->roadNumber,
            fromPointNumber: $command->fromPointNumber,
            fromAbscissa: $command->fromAbscissa,
            fromSide: $command->fromSide,
            toPointNumber: $command->toPointNumber,
            toAbscissa: $command->toAbscissa,
            toSide: $command->toSide,
            geometry: $geometry,
        );

        return $command->location;
    }

    private function computeLaneGeometry(SaveLocationCommand $command): ?string
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

    private function computeRoadSectionGeometry(SaveLocationCommand $command): string
    {
        $fullDepartmentalRoadGeometry = $this->roadGeocoder->computeRoad($command->roadNumber, $command->administrator);

        return $this->roadSectionMaker->computeSection(
            $fullDepartmentalRoadGeometry,
            $command->administrator,
            $command->roadNumber,
            $command->fromPointNumber,
            $command->fromSide,
            $command->fromAbscissa,
            $command->toPointNumber,
            $command->toSide,
            $command->toAbscissa,
        );
    }

    private function shouldRecomputeLaneGeometry(SaveLocationCommand $command): bool
    {
        return $command->cityCode !== $command->location->getCityCode()
            || $command->roadName !== $command->location->getRoadName()
            || ($command->fromHouseNumber !== $command->location->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->location->getToHouseNumber());
    }

    private function shouldRecomputeRoadSectionGeometry(SaveLocationCommand $command): bool
    {
        return $command->roadNumber !== $command->location->getRoadNumber()
            || $command->administrator !== $command->location->getAdministrator()
            || $command->fromPointNumber !== $command->location->getFromPointNumber()
            || $command->toPointNumber !== $command->location->getToPointNumber()
            || $command->fromAbscissa !== $command->location->getFromAbscissa()
            || $command->toAbscissa !== $command->location->getToAbscissa()
            || $command->fromSide !== $command->location->getFromSide()
            || $command->toSide !== $command->location->getToSide();
    }
}
