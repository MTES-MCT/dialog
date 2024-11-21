<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\EndAbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\Exception\StartAbscissaOutOfRangeException;
use App\Application\LineSectionMakerInterface;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;

final class RoadSectionMaker implements RoadSectionMakerInterface
{
    public function __construct(
        private LineSectionMakerInterface $lineSectionMaker,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function computeSection(
        string $fullDepartmentalRoadGeometry,
        string $administrator,
        string $roadNumber,
        string $fromPointNumber,
        string $fromSide,
        ?int $fromAbscissa,
        string $toPointNumber,
        string $toSide,
        int $toAbscissa,
    ): string {
        try {
            $fromCoords = $this->roadGeocoder
                ->computeReferencePoint($administrator, $roadNumber, $fromPointNumber, $fromSide, $fromAbscissa);
        } catch (AbscissaOutOfRangeException $e) {
            throw new StartAbscissaOutOfRangeException(previous: $e);
        } catch (GeocodingFailureException $e) {
            throw new RoadGeocodingFailureException(previous: $e);
        }

        try {
            $toCoords = $this->roadGeocoder
                ->computeReferencePoint($administrator, $roadNumber, $toPointNumber, $toSide, $toAbscissa);
        } catch (AbscissaOutOfRangeException $e) {
            throw new EndAbscissaOutOfRangeException(previous: $e);
        } catch (GeocodingFailureException $e) {
            throw new RoadGeocodingFailureException(previous: $e);
        }

        try {
            return $this->lineSectionMaker->computeSection(
                $fullDepartmentalRoadGeometry,
                $fromCoords,
                $toCoords,
            );
        } catch (GeocodingFailureException $e) {
            throw new RoadGeocodingFailureException(previous: $e);
        }
    }
}
