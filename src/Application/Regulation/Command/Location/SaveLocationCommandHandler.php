<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadLine;
use App\Application\RoadLineSectionMakerInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationRepository,
        private RoadGeocoderInterface $roadGeocoder,
        private RoadLineSectionMakerInterface $roadLineSectionMaker,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();

        // Create location if needed
        if (!$command->location instanceof Location) {
            if ($command->roadType === RoadTypeEnum::LANE->value) {
                $roadLine = $this->computeRoadLine($command);
                $geometry = empty($command->geometry) ? $this->computeLaneGeometry($command, $roadLine) : $command->geometry;
            } else {
                $roadLine = null;
                $geometry = null;
            }

            $location = $this->locationRepository->add(
                new Location(
                    uuid: $this->idFactory->make(),
                    measure: $command->measure,
                    roadType: $command->roadType,
                    administrator: $command->administrator,
                    roadNumber: $command->roadNumber,
                    cityLabel: $command->cityLabel,
                    cityCode: $command->cityCode,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    toHouseNumber: $command->toHouseNumber,
                    geometry: $geometry,
                    roadLineGeometry: $roadLine?->geometry,
                    roadLineId: $roadLine?->id,
                ),
            );

            $command->measure->addLocation($location);

            return $location;
        }

        if ($command->roadType === RoadTypeEnum::LANE->value) {
            $roadLine = $this->shouldRecomputeRoadLine($command)
                ? $this->computeRoadLine($command)
                : $command->location->getRoadLine();

            $geometry = $this->shouldRecomputeLaneGeometry($command)
                ? $this->computeLaneGeometry($command, $roadLine)
                : $command->location->getGeometry();
        } else {
            $roadLine = null;
            $geometry = null;
        }

        $command->location->update(
            roadType: $command->roadType,
            administrator: $command->administrator,
            roadNumber: $command->roadNumber,
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            toHouseNumber: $command->toHouseNumber,
            geometry: $geometry,
            roadLineGeometry: $roadLine?->geometry,
            roadLineId: $roadLine?->id,
        );

        return $command->location;
    }

    private function computeRoadLine(SaveLocationCommand $command): RoadLine
    {
        return $this->roadGeocoder->computeRoadLine($command->roadName, $command->cityCode);
    }

    private function shouldRecomputeRoadLine(SaveLocationCommand $command): bool
    {
        return !$command->location->getRoadLine() // For migration
            || $command->cityCode !== $command->location->getCityCode()
            || $command->roadName !== $command->location->getRoadName();
    }

    private function computeLaneGeometry(SaveLocationCommand $command, RoadLine $roadLine): string
    {
        $hasNoEnds = (
            !$command->fromHouseNumber
            && !$command->fromRoadName
            && !$command->toHouseNumber
            && !$command->toRoadName
        );

        if ($hasNoEnds) {
            return $roadLine->geometry;
        }

        return $this->roadLineSectionMaker->computeRoadLineSection(
            $roadLine,
            $command->fromHouseNumber,
            $command->fromRoadName,
            $command->toHouseNumber,
            $command->toRoadName,
        );
    }

    private function shouldRecomputeLaneGeometry(SaveLocationCommand $command): bool
    {
        return !$command->location->getRoadLine() // For migration
            || $command->cityCode !== $command->location->getCityCode()
            || $command->roadName !== $command->location->getRoadName()
            || ($command->fromHouseNumber !== $command->location->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->location->getToHouseNumber());
    }
}