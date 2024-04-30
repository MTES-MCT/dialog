<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\LaneSectionMakerInterface;
use App\Application\QueryInterface;
use App\Application\RoadGeocoderInterface;

final class GetNamedStreetGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private LaneSectionMakerInterface $laneSectionMaker,
    ) {
    }

    public function __invoke(GetNamedStreetGeometryQuery $query): string
    {
        if ($query->geometry) {
            return $query->geometry;
        }

        if ($query->location && !$this->shouldRecomputeGeometry($query)) {
            return $query->location->getGeometry();
        }

        return $this->computeGeometry($query);
    }

    private function computeGeometry(GetNamedStreetGeometryQuery $query): ?string
    {
        $command = $query->command;

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

    private function shouldRecomputeGeometry(GetNamedStreetGeometryQuery $query): bool
    {
        $command = $query->command;

        return !$command->namedStreet
            || $command->cityCode !== $command->namedStreet->getCityCode()
            || $command->roadName !== $command->namedStreet->getRoadName()
            || ($command->fromHouseNumber !== $command->namedStreet->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->namedStreet->getToHouseNumber());
    }
}
