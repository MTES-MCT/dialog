<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Application\GeometryServiceInterface;
use App\Application\RoadLine;
use App\Application\RoadLineSectionMakerInterface;
use App\Domain\Geography\HouseNumber;

final class PostGisRoadLineSectionMaker implements RoadLineSectionMakerInterface
{
    public function __construct(
        private GeocoderInterface $geocoder,
        private GeometryServiceInterface $geometryService,
    ) {
    }

    public function computeRoadLineSection(
        RoadLine $roadLine,
        string|null $fromHouseNumber,
        string|null $fromRoadName,
        string|null $toHouseNumber,
        string|null $toRoadName,
    ): string {
        if ($fromHouseNumber) {
            $fromAddress = sprintf('%s %s', $fromHouseNumber, $roadLine->roadName);
            $fromCoords = $this->geocoder->computeCoordinates($fromAddress, $roadLine->cityCode);
        } elseif ($fromRoadName) {
            $fromCoords = $this->geocoder->computeJunctionCoordinates($roadLine->roadName, $fromRoadName, $roadLine->cityCode);
        } else {
            $fromCoords = null;
        }

        if ($toHouseNumber) {
            $toAddress = sprintf('%s %s', $toHouseNumber, $roadLine->roadName);
            $toCoords = $this->geocoder->computeCoordinates($toAddress, $roadLine->cityCode);
        } elseif ($toRoadName) {
            $toCoords = $this->geocoder->computeJunctionCoordinates($roadLine->roadName, $toRoadName, $roadLine->cityCode);
        } else {
            $toCoords = null;
        }

        // Compute the "fraction" position (a number between 0 and 1, as per https://postgis.net/docs/ST_LineLocatePoint.html)
        // of the from/to house numbers.
        //
        // Here is an example for a street with house numbers between 1 and 25, fromHouseNumber = 3 and toHouseNumber = 20:
        // House numbers: 1 |-3-----------------20---| 25
        // Fractions:     0 |-0.12--------------0.8--| 1
        //                    ^ fromFraction   ^toFraction

        $fromFraction = $fromCoords ? $this->geometryService->locatePointOnLine($roadLine->geometry, $fromCoords) : null;
        $toFraction = $toCoords ? $this->geometryService->locatePointOnLine($roadLine->geometry, $toCoords) : null;

        // Now we want the start and end fractions at which the road line should be clipped,
        // ensuring that 0 <= $startFraction <= $endFraction <= 1 (requirement of ST_LineSubstring).
        //
        // $fromFraction should be used as $startFraction IF AND ONLY IF the ordering of house numbers is the same as
        // the ordering of points in the road line.
        //
        // If the orderings are opposite, we would have something like this:
        // House numbers: 25 |--20-----------------3---| 1
        // Fractions:     0  |--0.2---------------0.88-| 1 (*)
        //                       ^toFraction       ^fromFraction
        // The house number 25 is at the beginning of the road line (fraction 0), and the house number 1 is at the end of the road line (fraction 1).
        // In that case, fractions must be "swapped": $toFraction should be used as $startFraction, and $fromFraction should be used as $endFraction.

        $hasBothEnds = $fromFraction !== null && $toFraction !== null;

        if ($hasBothEnds) {
            // This is the "easy" situation depicted in (*) above.
            $houseNumberOrderingMatchesLinePointsOrdering = $fromFraction <= $toFraction;
        } else {
            // Only one end of the road was requested. We must find a comparison point to detect the ordering of house numbers.
            // We do this by computing the house number of the first point in the road line.
            //
            // For example, if the user requested fromHouseNumber = 3, and the orderings are identical, we would have:
            // House numbers: 1 |--3----------------------| 25
            // Fractions:     0 |--0.12-------------------| 1
            //                      ^fromFraction
            // The house number of the first point is 1, which is BEFORE the requested house number 3.
            // So $fromFraction should be used as the $startFraction, and $endFraction will be 1.
            //
            // But if the orderings are opposite, we would have:
            // House numbers: 25 |---------------------3---| 1
            // Fractions:     0  |--------------------0.88-| 1
            //                                         ^fromFraction
            // The house number of the first point is 25, which is AFTER the requested house number 3.
            // So $fromFraction should be used as the $endFraction, and the $startFraction should be 0.
            //
            // The same swapping rule must be applied if only a toHouseNumber was requested.

            $firstPoint = $this->geometryService->getFirstPointOfLinestring($roadLine->geometry);

            try {
                $houseNumberOfFirstLinePoint = $this->geocoder->findHouseNumberOnRoad($roadLine->id, $firstPoint);
            } catch (GeocodingFailureException $exc) {
                // For some reason the house number of the first point could not be found.
                // There's probably something very wrong. Let the exception bubble up...
                throw $exc;
            }

            $houseNumberOrderingMatchesLinePointsOrdering = HouseNumber::compare($houseNumberOfFirstLinePoint, $fromHouseNumber ?: $toHouseNumber);
        }

        [$startFraction, $endFraction] = $houseNumberOrderingMatchesLinePointsOrdering
            ? [$fromFraction, $toFraction]
            : [$toFraction, $fromFraction];

        return $this->geometryService->clipLine($roadLine->geometry, $startFraction ?: 0, $endFraction ?: 1);
    }
}
