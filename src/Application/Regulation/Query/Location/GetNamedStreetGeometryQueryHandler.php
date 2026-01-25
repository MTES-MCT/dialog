<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\Exception\EmptyRoadBanIdException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\LaneSectionMakerInterface;
use App\Application\QueryInterface;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\RoadGeocoderInterface;

final class GetNamedStreetGeometryQueryHandler implements QueryInterface
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private LaneSectionMakerInterface $laneSectionMaker,
    ) {
    }

    public function __invoke(GetNamedStreetGeometryQuery $query): ?string
    {
        if ($query->geometry) {
            return $query->geometry;
        }

        if ($query->location && !$this->shouldRecomputeGeometry($query)) {
            return $query->location->getGeometry();
        }

        return $this->computeGeometry($query);
    }

    private function computeGeometry(GetNamedStreetGeometryQuery $query): string
    {
        $command = $query->command;
        $command->clean();

        if (!$command->roadName) {
            throw new GeocodingFailureException('not implemented: full city geocoding');
        }

        if ($command->fromRoadName && !$command->fromRoadBanId) {
            throw new EmptyRoadBanIdException();
        }

        if ($command->toRoadName && !$command->toRoadBanId) {
            throw new EmptyRoadBanIdException();
        }

        $hasNoStart = !$command->fromCoords && !$command->fromHouseNumber && !$command->fromRoadName;
        $hasNoEnd = !$command->toCoords && !$command->toHouseNumber && !$command->toRoadName;

        $fullLaneGeometry = $this->getFullGeometryLane($command);

        if ($hasNoStart && $hasNoEnd) {
            return $fullLaneGeometry;
        }

        if (!$command->roadBanId) {
            $command->roadBanId = $this->roadGeocoder->getRoadBanIdFromName($command->roadName, $command->cityCode);
        }

        return $this->laneSectionMaker->computeSection(
            $fullLaneGeometry,
            $command->roadBanId,
            $command->roadName,
            $command->cityCode,
            $command->direction,
            $command->fromCoords,
            $command->fromHouseNumber,
            $command->fromRoadBanId,
            $command->toCoords,
            $command->toHouseNumber,
            $command->toRoadBanId,
        );
    }

    private function getFullGeometryLane(SaveNamedStreetCommand $command): string
    {
        if ($command->roadBanId) {
            return $this->roadGeocoder->computeRoadLine($command->roadBanId);
        }

        if ($command->fromRoadBanId) {
            return $this->roadGeocoder->computeRoadLineFromName($command->fromRoadName, $command->cityCode);
        }

        return $this->roadGeocoder->computeRoadLineFromName($command->roadName, $command->cityCode);
    }

    private function shouldRecomputeGeometry(GetNamedStreetGeometryQuery $query): bool
    {
        $command = $query->command;

        return !$command->namedStreet
            || $command->direction !== $command->namedStreet->getDirection()
            || $command->roadBanId !== $command->namedStreet->getRoadBanId()
            || ($command->fromHouseNumber !== $command->namedStreet->getFromHouseNumber())
            || ($command->fromRoadName !== $command->namedStreet->getFromRoadName())
            || ($command->toHouseNumber !== $command->namedStreet->getToHouseNumber())
            || ($command->toRoadName !== $command->namedStreet->getToRoadName());
    }
}
