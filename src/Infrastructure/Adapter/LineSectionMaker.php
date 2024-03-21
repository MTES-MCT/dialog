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
        $pointGeoJson = $point->asGeoJSON();

        try {
            $row = $this->em->getConnection()->fetchAssociative(
                'SELECT ST_LineLocatePoint(ST_LineMerge(:geom), :point) AS t',
                ['geom' => $lineGeometry, 'point' => $pointGeoJson],
            );

            return (float) $row['t'];
        } catch (DriverException $exc) {
            throw new GeocodingFailureException(
                sprintf(
                    'Failed to locate point %s on line %s: is this a MultiLineString not mergeable into a single LineString?',
                    $pointGeoJson,
                    $lineGeometry,
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
    public function computeSection(
        string $lineGeometry,
        Coordinates $fromCoords,
        Coordinates $toCoords,
    ): string {
        $fromFraction = $this->locatePointOnLine($lineGeometry, $fromCoords);
        $toFraction = $this->locatePointOnLine($lineGeometry, $toCoords);

        if ($fromFraction > $toFraction) {
            [$fromFraction, $toFraction] = [$toFraction, $fromFraction];
        }

        return $this->clipLine($lineGeometry, $fromFraction, $toFraction);
    }
}
