<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\DepartmentalRoadGeocodingFailureException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Application\LaneSectionMakerInterface;
use App\Application\LineSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;

final class LaneSectionMaker implements LaneSectionMakerInterface
{
    public function __construct(
        private GeocoderInterface $geocoder,
        private LineSectionMakerInterface $lineSectionMaker,
    ) {
    }

    private function resolvePoint(string $roadName, string $cityCode, ?string $houseNumber, ?string $intersectingRoadName): Coordinates
    {
        if ($houseNumber) {
            $fromAddress = sprintf('%s %s', $houseNumber, $roadName);

            return $this->geocoder->computeCoordinates($fromAddress, $cityCode);
        }

        return $this->geocoder->computeJunctionCoordinates($roadName, $intersectingRoadName, $cityCode);
    }

    /**
     * @throws GeocodingFailureException|DepartmentalRoadGeocodingFailureException
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
        if (!$fromCoords) {
            $fromCoords = $this->resolvePoint($roadName, $cityCode, $fromHouseNumber, $fromRoadName);
        }

        if (!$toCoords) {
            $toCoords = $this->resolvePoint($roadName, $cityCode, $toHouseNumber, $toRoadName);
        }

        return $this->lineSectionMaker->computeSection(RoadTypeEnum::LANE, $fullLaneGeometry, $fromCoords, $toCoords);
    }
}
