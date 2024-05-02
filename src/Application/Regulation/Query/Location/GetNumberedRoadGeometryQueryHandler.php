<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;

final class GetNumberedRoadGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private RoadSectionMakerInterface $roadSectionMaker,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(GetNumberedRoadGeometryQuery $query): string
    {
        if ($query->geometry) {
            return $query->geometry;
        }

        if ($query->location && !$this->shouldRecomputeGeometry($query)) {
            return $query->location->getGeometry();
        }

        return $this->computeGeometry($query);
    }

    private function computeGeometry(GetNumberedRoadGeometryQuery $query): string
    {
        $command = $query->command;

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

    private function shouldRecomputeGeometry(GetNumberedRoadGeometryQuery $query): bool
    {
        $command = $query->command;

        return !$command->numberedRoad
            || $command->roadNumber !== $command->numberedRoad->getRoadNumber()
            || $command->administrator !== $command->numberedRoad->getAdministrator()
            || $command->fromPointNumber !== $command->numberedRoad->getFromPointNumber()
            || $command->toPointNumber !== $command->numberedRoad->getToPointNumber()
            || $command->fromAbscissa !== $command->numberedRoad->getFromAbscissa()
            || $command->toAbscissa !== $command->numberedRoad->getToAbscissa()
            || $command->fromSide !== $command->numberedRoad->getFromSide()
            || $command->toSide !== $command->numberedRoad->getToSide();
    }
}
