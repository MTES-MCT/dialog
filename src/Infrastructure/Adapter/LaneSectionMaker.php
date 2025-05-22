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
        private IntersectionGeocoderInterface $intersectionGeocoder,
        private LineSectionMakerInterface $lineSectionMaker,
    ) {
    }

    private function resolveAddress(string $houseNumber, string $roadName, string $cityCode): Coordinates
    {
        $fromAddress = \sprintf('%s %s', $houseNumber, $roadName);

        return $this->geocoder->computeCoordinates($fromAddress, $cityCode);
    }

    /**
     * @throws LaneGeocodingFailureException
     */
    public function computeSection(
        string $fullLaneGeometry,
        string $roadBanId,
        string $roadName,
        string $cityCode,
        string $direction,
        ?Coordinates $fromCoords,
        ?string $fromHouseNumber,
        ?string $fromRoadBanId,
        ?Coordinates $toCoords,
        ?string $toHouseNumber,
        ?string $toRoadBanId,
    ): string {
        try {
            if (!$fromCoords) {
                $fromCoords = $fromHouseNumber
                    ? $this->resolveAddress($fromHouseNumber, $roadName, $cityCode)
                    : $this->intersectionGeocoder->computeIntersection($roadBanId, $fromRoadBanId);
            }

            if (!$toCoords) {
                $toCoords = $toHouseNumber
                    ? $this->resolveAddress($toHouseNumber, $roadName, $cityCode)
                    : $this->intersectionGeocoder->computeIntersection($roadBanId, $toRoadBanId);
            }

            // NOTE : Rien à faire pour le cas A vers B, on mettra le fait qu'une seule direction est concernée comme métadonnée dans les exports DATEX / CIFS / etc.
            if ($direction === DirectionEnum::B_TO_A->value) {
                [$fromCoords, $toCoords] = [$toCoords, $fromCoords];
            }

            return $this->lineSectionMaker->computeSection($fullLaneGeometry, $fromCoords, $toCoords);
        } catch (GeocodingFailureException $exc) {
            throw new LaneGeocodingFailureException(previous: $exc);
        }
    }
}
