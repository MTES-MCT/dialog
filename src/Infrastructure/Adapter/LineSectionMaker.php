<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\LineSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;

final class LineSectionMaker implements LineSectionMakerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    private function locatePointOnLine(string $lineGeometry, Coordinates $point): float
    {
        $pointGeoJson = $point->asGeoJSON(includeCrs: str_contains($lineGeometry, '"crs"'));

        try {
            $row = $this->em->getConnection()->fetchAssociative(
                'SELECT ST_LineLocatePoint(ST_LineMerge(:geom), :point) AS t',
                ['geom' => $lineGeometry, 'point' => $pointGeoJson],
            );

            return (float) $row['t'];
        } catch (DriverException $exc) {
            throw new GeocodingFailureException(
                sprintf(
                    'Failed to locate point %s on line %s: %s',
                    $pointGeoJson,
                    $lineGeometry,
                    $exc->getMessage(),
                ),
                previous: $exc,
            );
        }
    }

    private function locateReferencePointOnLine(string $lineGeometry, int $abscissa): string
    {
        try {
            $row = $this->em->getConnection()->fetchAssociative(
                '
                    SELECT ST_AsGeoJSON(
                        ST_LocateAlong(
                            ST_AddMeasure(
                                :geom,
                                0,
                                :abscissa
                            ),
                            6
                        )
                    ) as point
                ',
                ['geom' => $lineGeometry, 'abscissa' => $abscissa],
            );

            return $row['point'];
        } catch (DriverException $exc) {
            throw new GeocodingFailureException(
                sprintf(
                    'Failed to locate abscissa %s on line %s: %s',
                    $abscissa,
                    $lineGeometry,
                    $exc->getMessage(),
                ),
                previous: $exc,
            );
        }
    }

    private function clipLine(string $lineGeometry, float $startFraction = 0, float $endFraction = 1): string
    {
        $row = $this->em->getConnection()->fetchAssociative(
            'SELECT ST_AsGeoJSON(ST_LineSubstring(ST_LineMerge(:geom), :startFraction, :endFraction)) AS line',
            [
                'geom' => $lineGeometry,
                'startFraction' => $startFraction,
                'endFraction' => $endFraction,
            ],
        );

        return $row['line'];
    }

    /**
     * @throws GeocodingFailureException
     */
    public function computeDepartmentalSection(
        string $lineGeometry,
        int $abscissaA,
        int $abscissaB,
    ): string {
        $fromMeasure = $this->locateReferencePointOnLine($lineGeometry, $abscissaA);
        $toMeasure = $this->locateReferencePointOnLine($lineGeometry, $abscissaB);
dd($fromMeasure, $toMeasure);
        if ($fromMeasure > $toMeasure) {
            [$fromMeasure, $toMeasure] = [$toMeasure, $fromMeasure];
        }

        return $this->clipLine($lineGeometry, $fromMeasure, $toMeasure);
    }

    public function computeSection(
        string $lineGeometry,
        Coordinates $fromCoords,
        Coordinates $toCoords,
    ): string {
        $fromMeasure = $this->locatePointOnLine($lineGeometry, $fromCoords);
        $toMeasure = $this->locatePointOnLine($lineGeometry, $toCoords);

        if ($fromMeasure > $toMeasure) {
            [$fromMeasure, $toMeasure] = [$toMeasure, $fromMeasure];
        }

        return $this->clipLine($lineGeometry, $fromMeasure, $toMeasure);
    }
}
