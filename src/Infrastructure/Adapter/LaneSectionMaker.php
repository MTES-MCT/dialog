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
use App\Domain\Regulation\Enum\DirectionEnum;

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
        string $direction,
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

            // NOTE : Rien à faire pour le cas A vers B, on mettra le fait qu'une seule direction comme métadonnée dans les exports DATEX / CIFS / etc.
            if ($direction === DirectionEnum::B_TO_A->value) {
                [$fromCoords, $toCoords] = [$toCoords, $fromCoords];
            }

            return $this->lineSectionMaker->computeSection($fullLaneGeometry, $fromCoords, $toCoords);
        } catch (GeocodingFailureException $exc) {
            throw new LaneGeocodingFailureException(previous: $exc);
        }
    }
}
