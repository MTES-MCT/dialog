<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\GeometryServiceInterface;
use App\Domain\Geography\Coordinates;
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

    public function locatePointOnLine(string $line, Coordinates $point): float
    {
        $row = $this->locatePointOnLineStmt->execute([
            'geom' => $line,
            'pt' => $point->asGeoJSON(),
        ])->fetchAssociative();

        return (float) $row['t'];
    }

    public function getFirstPointOfLinestring(string $line): Coordinates
    {
        $row = $this->firstPointOfLinestringStmt->execute([
            'geom' => $line,
        ])->fetchAssociative();

        return Coordinates::fromLonLat((float) $row['x'], (float) $row['y']);
    }

    public function clipLine(string $line, float $startFraction = 0, float $endFraction = 1): string
    {
        $row = $this->clipLineStmt->execute([
            'geom' => $line,
            'startFraction' => $startFraction,
            'endFraction' => $endFraction,
        ])->fetchAssociative();

        return $row['line'];
    }
}
