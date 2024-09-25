<?php

declare(strict_types=1);

namespace App\Infrastructure\Cifs;

use App\Application\Cifs\PolylineMakerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class PolylineMaker implements PolylineMakerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function attemptMergeLines(string $geometry): ?string
    {
        $row = $this->em
            ->getConnection()
            ->fetchAssociative(
                'SELECT ST_AsGeoJSON(ST_LineMerge(:geom)) AS geom',
                ['geom' => $geometry],
            );

        return $row['geom'] ?? $geometry;
    }

    public function getPolylines(string $geometry): array
    {
        $rows = $this->em
            ->getConnection()
            ->fetchAllAssociative(
                'WITH linestring AS (
                    -- Split the geometry into its individual LINESTRING components
                    SELECT (components.dump).geom AS geom FROM (
                        SELECT ST_Dump(ST_Multi(:geom)) as dump
                    ) AS components
                    WHERE ST_Dimension((components.dump).geom) >= 1 -- Remove (MULTI)POINT components
                    AND ST_NPoints((components.dump).geom) >= 2 -- Remove single-point LINESTRING components
                )
                SELECT array_to_string(
                    array(
                        SELECT ST_Y(d.geom) || \' \' || ST_X(d.geom)
                        FROM ST_DumpPoints(linestring.geom) AS d
                    ),
                    \' \'
                ) AS polyline
                FROM linestring',
                ['geom' => $geometry],
            );

        $polylines = [];

        foreach ($rows as $row) {
            $polylines[] = $row['polyline'];
        }

        return $polylines;
    }
}
