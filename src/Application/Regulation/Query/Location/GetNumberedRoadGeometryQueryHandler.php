<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\PointNumberSideDetectorInterface;
use App\Application\QueryInterface;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;

final class GetNumberedRoadGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private RoadSectionMakerInterface $roadSectionMaker,
        private RoadGeocoderInterface $roadGeocoder,
        private PointNumberSideDetectorInterface $pointNumberSideDetector,
    ) {
    }

    public function __invoke(GetNumberedRoadGeometryQuery $query): string
    {
        $command = $query->command;

        [$command->fromSide, $command->toSide] = $this->pointNumberSideDetector->detect(
            $command->direction,
            $command->administrator,
            $command->roadNumber,
            $command->fromPointNumber,
            $command->fromAbscissa ?? 0,
            $command->toPointNumber,
            $command->toAbscissa ?? 0,
        );

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

        $fullRoadGeometry = $this->roadGeocoder->computeRoad($command->roadType, $command->administrator, $command->roadNumber);

        return $this->roadSectionMaker->computeSection(
            $fullRoadGeometry,
            $command->roadType,
            $command->administrator,
            $command->roadNumber,
            $command->fromPointNumber,
            $command->fromSide,
            $command->fromAbscissa ?? 0,
            $command->toPointNumber,
            $command->toSide,
            $command->toAbscissa ?? 0,
            $command->direction,
        );
    }

    private function shouldRecomputeGeometry(GetNumberedRoadGeometryQuery $query): bool
    {
        $command = $query->command;
        $numberedRoad = $command->numberedRoad;

        return !$numberedRoad
            || $command->roadNumber !== $numberedRoad->getRoadNumber()
            || $command->administrator !== $numberedRoad->getAdministrator()
            || $command->fromPointNumber !== $numberedRoad->getFromPointNumber()
            || $command->toPointNumber !== $numberedRoad->getToPointNumber()
            || $command->fromAbscissa !== $numberedRoad->getFromAbscissa()
            || $command->toAbscissa !== $numberedRoad->getToAbscissa()
            || $command->fromSide !== $numberedRoad->getFromSide()
            || $command->toSide !== $numberedRoad->getToSide();
    }
}
