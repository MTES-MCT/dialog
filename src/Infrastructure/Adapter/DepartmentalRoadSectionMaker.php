<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\DepartmentalRoadSectionMakerInterface;
use App\Application\Exception\DepartmentalRoadGeocodingFailureException;
use App\Application\LineSectionMakerInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;

final class DepartmentalRoadSectionMaker implements DepartmentalRoadSectionMakerInterface
{
    public function __construct(
        private LineSectionMakerInterface $lineSectionMaker,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    /**
     * @throws DepartmentalRoadGeocodingFailureException
     */
    public function computeSection(
        string $fullDepartmentalRoadGeometry,
        string $administrator,
        string $roadNumber,
        string $fromPointNumber,
        string $fromSide,
        ?int $fromAbscissa,
        string $toPointNumber,
        string $toSide,
        ?int $toAbscissa,
    ): string {
        $fromCoords = $this->roadGeocoder
            ->computeReferencePoint($fullDepartmentalRoadGeometry, $administrator, $roadNumber, $fromPointNumber, $fromSide, $fromAbscissa);
        $toCoords = $this->roadGeocoder
            ->computeReferencePoint($fullDepartmentalRoadGeometry, $administrator, $roadNumber, $toPointNumber, $toSide, $toAbscissa);

        return $this->lineSectionMaker->computeSection(
            RoadTypeEnum::DEPARTMENTAL_ROAD,
            $fullDepartmentalRoadGeometry,
            $fromCoords,
            $toCoords,
        );
    }
}
