<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\LaneGeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Application\IntersectionGeocoderInterface;
use App\Application\LaneSectionMakerInterface;
use App\Application\LineSectionMakerInterface;
use App\Domain\Geography\Coordinates;

final class LaneSectionMaker implements LaneSectionMakerInterface
{
    public function __construct(
        private GeocoderInterface $geocoder,
        private IntersectionGeocoderInterface $intersectionGeocder,
        private LineSectionMakerInterface $lineSectionMaker,
    ) {
    }

    private function resolvePoint(string $roadName, string $cityCode, ?string $houseNumber, ?string $intersectingRoadName): Coordinates
    {
        if ($houseNumber) {
            $fromAddress = \sprintf('%s %s', $houseNumber, $roadName);

            return $this->geocoder->computeCoordinates($fromAddress, $cityCode);
        }

        return $this->intersectionGeocder->computeIntersection($roadName, $intersectingRoadName, $cityCode);
    }

    /**
     * @throws LaneGeocodingFailureException
     */
    public function computeSection(
        string $fullLaneGeometry,
        string $roadName,
        string $cityCode,
        ?Coordinates $fromCoords,
        ?string $fromHouseNumber,
        ?string $fromRoadName,
        ?Coordinates $toCoords,
        ?string $toHouseNumber,
        ?string $toRoadName,
    ): string {
        try {
            if (!$fromCoords) {
                $fromCoords = $this->resolvePoint($roadName, $cityCode, $fromHouseNumber, $fromRoadName);
            }

            if (!$toCoords) {
                $toCoords = $this->resolvePoint($roadName, $cityCode, $toHouseNumber, $toRoadName);
            }

            return $this->lineSectionMaker->computeSection($fullLaneGeometry, $fromCoords, $toCoords);
        } catch (GeocodingFailureException $exc) {
            throw new LaneGeocodingFailureException(previous: $exc);
        }
    }
}
