<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\DepartmentalRoadSectionMakerInterface;
use App\Application\LineSectionMakerInterface;
use App\Application\ReferencePointGeocoderInterface;

final class DepartmentalRoadSectionMaker implements DepartmentalRoadSectionMakerInterface
{
    public function __construct(
        private ReferencePointGeocoderInterface $referencePointGeocoder,
        private LineSectionMakerInterface $lineSectionMaker,
    ) {
    }

    public function computeSection(
        string $fullDepartmentalRoadGeometry,
        string $administrator,
        string $roadNumber,
        string $direction,
        array $pointA,
        array $pointB,
    ): string {
        // todo : gestion d'erreur
        $coordinatesA = $this->referencePointGeocoder->compute($administrator, $roadNumber, $direction, $pointA['pointNumber'], $pointA['abscissa']);
        $coordinatesB = $this->referencePointGeocoder->compute($administrator, $roadNumber, $direction, $pointB['pointNumber'], $pointB['abscissa']);

        return $this->lineSectionMaker->computeSection($fullDepartmentalRoadGeometry, $coordinatesA, $coordinatesB);
    }
}
