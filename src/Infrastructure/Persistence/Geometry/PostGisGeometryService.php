<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Geometry;

use App\Application\Exception\GeocodingFailureException;
use App\Application\GeometryServiceInterface;
use App\Domain\Geography\Coordinates;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;

final class PostGisGeometryService implements GeometryServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function locatePointOnLine(string $lineGeometry, Coordinates $point): float
    {
        $stmt = $this->em->getConnection()->prepare(
            'SELECT ST_LineLocatePoint(ST_LineMerge(:geom), :pt) AS t',
        );

        $pointGeoJson = $point->asGeoJSON();

        try {
            $row = $stmt->executeQuery([
                'geom' => $lineGeometry,
                'pt' => $pointGeoJson,
            ])->fetchAssociative();

            return (float) $row['t'];
        } catch (DriverException $exc) {
            throw new GeocodingFailureException(
                sprintf(
                    'Failed to locate point %s on line %s: is line a MultiLineString not mergeable into a single LineString?',
                    $pointGeoJson,
                    $lineGeometry,
                ),
                previous: $exc,
            );
        }
    }

    public function getFirstPointOfLinestring(string $lineGeometry): Coordinates
    {
        $stmt = $this->em->getConnection()->prepare(
            'SELECT
                ST_X(ST_StartPoint(ST_LineMerge(:geom))) AS x,
                ST_Y(ST_StartPoint(ST_LineMerge(:geom))) AS y',
        );

        $row = $stmt->executeQuery([
            'geom' => $lineGeometry,
        ])->fetchAssociative();

        return Coordinates::fromLonLat((float) $row['x'], (float) $row['y']);
    }

    public function clipLine(string $lineGeometry, float $startFraction = 0, float $endFraction = 1): string
    {
        $stmt = $this->em->getConnection()->prepare(
            'SELECT ST_AsGeoJSON(ST_LineSubstring(ST_LineMerge(:geom), :startFraction, :endFraction)) AS line',
        );

        $row = $stmt->executeQuery([
            'geom' => $lineGeometry,
            'startFraction' => $startFraction,
            'endFraction' => $endFraction,
        ])->fetchAssociative();

        return $row['line'];
    }
}
