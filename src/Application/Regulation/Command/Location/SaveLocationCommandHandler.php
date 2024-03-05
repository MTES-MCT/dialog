<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\Exception\GeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Application\GeometryServiceInterface;
use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\HouseNumber;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class SaveLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private LocationRepositoryInterface $locationRepository,
        private GeocoderInterface $geocoder,
        private RoadGeocoderInterface $roadGeocoder,
        private GeometryServiceInterface $geometryService,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();

        // Create location if needed
        if (!$command->location instanceof Location) {
            $geometry = empty($command->geometry) ? $this->computeGeometry($command) : $command->geometry;

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
                ),
            );

            $command->measure->addLocation($location);

            return $location;
        }

        $geometry = $this->shouldRecomputeGeometry($command)
            ? $this->computeGeometry($command)
            : $command->location->getGeometry();

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
        );

        return $command->location;
    }

    private function computeGeometry(SaveLocationCommand $command): string
    {
        $roadLine = $this->roadGeocoder->computeRoadLine($command->roadName, $command->cityCode);

        if (!$command->fromHouseNumber && !$command->toHouseNumber) {
            return $roadLine->geometry;
        }

        // Compute the "fractions" (between 0 and 1) of the from/to house numbers.
        //
        // Here is an example for a street with house numbers between 1 and 25, fromHouseNumber = 3 and toHouseNumber = 20:
        // House numbers: 1 |-3-----------------20---| 25
        // Fractions:     0 |-0.12--------------0.8--| 1
        //                    ^ fromFraction   ^toFraction

        $fromFraction = null;
        $toFraction = null;

        if ($command->fromHouseNumber) {
            $fromAddress = sprintf('%s %s', $command->fromHouseNumber, $command->roadName);
            $fromCoords = $this->geocoder->computeCoordinates($fromAddress, $command->cityCode);
            $fromFraction = $this->geometryService->locatePointOnLine($roadLine->geometry, $fromCoords);
        }

        if ($command->toHouseNumber) {
            $toAddress = sprintf('%s %s', $command->toHouseNumber, $command->roadName);
            $toCoords = $this->geocoder->computeCoordinates($toAddress, $command->cityCode);
            $toFraction = $this->geometryService->locatePointOnLine($roadLine->geometry, $toCoords);
        }

        // Now we want the start and end fractions at which the road line should be clipped,
        // ensuring that 0 <= $startFraction <= $endFraction <= 1.
        //
        // $startFraction is $fromFraction ONLY IF the ordering of house numbers is the same as
        // the ordering of points in the road line.
        //
        // If the orderings are opposite, we would have something like this:
        // House numbers: 25 |--20-----------------3---| 1
        // Fractions:     0  |--0.2---------------0.88-| 1 (*)
        //                       ^toFraction       ^fromFraction
        // The house number 25 is at the beginning of the road line (fraction 0), and the house number 1 is at the end of the road line (fraction 1).
        // In that case, fractions must be "swapped": $startFraction must be $toFraction, and $endFraction must be $fromFraction.

        $shouldSwap = false;

        if ($fromFraction !== null && $toFraction !== null) {
            // This is the "easy" situation depicted in (*) above.
            $shouldSwap = $toFraction < $fromFraction;
        } elseif ($fromFraction !== null || $toFraction !== null) {
            // If only one extremity of the road was requested, we must find a comparison point to detect the ordering of house numbers.
            // We do this by computing the house number of the first point in the road line.
            //
            // For example if the user requested fromHouseNumber = 3, and the orderings are identical, we would have:
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

            $shouldSwap = !HouseNumber::compare($houseNumberOfFirstLinePoint, $command->fromHouseNumber ?: $command->toHouseNumber);
        }

        [$startFraction, $endFraction] = $shouldSwap ? [$toFraction, $fromFraction] : [$fromFraction, $toFraction];

        return $this->geometryService->clipLine($roadLine->geometry, $startFraction ?: 0, $endFraction ?: 1);
    }

    private function shouldRecomputeGeometry(SaveLocationCommand $command): bool
    {
        return $command->cityCode !== $command->location->getCityCode()
            || $command->roadName !== $command->location->getRoadName()
            || ($command->fromHouseNumber !== $command->location->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->location->getToHouseNumber());
    }
}
