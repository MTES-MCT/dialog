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
    private $locatePointOnLineStmt;
    private $clipLineStmt;
    private $firstPointOfLinestringStmt;

    public function __construct(
        EntityManagerInterface $em,
    ) {
        $conn = $em->getConnection();

        // Prepare statements in advance for reuse.
        $this->locatePointOnLineStmt = $conn->prepare('SELECT ST_LineLocatePoint(ST_LineMerge(:geom), :pt) AS t');
        $this->firstPointOfLinestringStmt = $conn->prepare('SELECT ST_X(ST_StartPoint(ST_LineMerge(:geom))) AS x, ST_Y(ST_StartPoint(ST_LineMerge(:geom))) AS y');
        $this->clipLineStmt = $conn->prepare('SELECT ST_AsGeoJSON(ST_LineSubstring(ST_LineMerge(:geom), :startFraction, :endFraction)) AS line');
    }

    public function locatePointOnLine(string $lineGeometry, Coordinates $point): float
    {
        $pointGeoJson = $point->asGeoJSON();

        try {
            $row = $this->locatePointOnLineStmt->execute([
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
                previous: $exc);
        }
    }

    public function getFirstPointOfLinestring(string $lineGeometry): Coordinates
    {
        $row = $this->firstPointOfLinestringStmt->execute([
            'geom' => $lineGeometry,
        ])->fetchAssociative();

        return Coordinates::fromLonLat((float) $row['x'], (float) $row['y']);
    }

    public function clipLine(string $lineGeometry, float $startFraction = 0, float $endFraction = 1): string
    {
        $row = $this->clipLineStmt->execute([
            'geom' => $lineGeometry,
            'startFraction' => $startFraction,
            'endFraction' => $endFraction,
        ])->fetchAssociative();

        return $row['line'];
    }
}
