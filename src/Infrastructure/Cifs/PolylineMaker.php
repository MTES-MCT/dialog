<?php

declare(strict_types=1);

namespace App\Infrastructure\Cifs;

use App\Application\Cifs\PolylineBuilder;
use App\Application\Cifs\PolylineMakerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class PolylineMaker implements PolylineMakerInterface
{
    private const GEOM_PARAM = 'geom';

    public function __construct(
        private EntityManagerInterface $em,
        private PolylineBuilder $polylineBuilder,
    ) {
    }

    public function normalizeToLineStringGeoJSON(string $geometry): ?string
    {
        $row = $this->em
            ->getConnection()
            ->fetchAssociative(
                'WITH base AS (
                    SELECT ST_GeomFromGeoJSON(:geom)::geometry AS g
                ),
                dumped AS (
                    SELECT (ST_Dump(ST_Multi(g))).geom AS geom FROM base
                ),
                lines_only AS (
                    SELECT geom FROM dumped
                    WHERE ST_Dimension(geom) = 1 AND ST_NPoints(geom) >= 2
                )
                SELECT ST_AsGeoJSON(ST_LineMerge(ST_Collect(geom))) AS geom
                FROM lines_only',
                [self::GEOM_PARAM => $geometry],
            );

        return isset($row['geom']) && $row['geom'] !== null ? $row['geom'] : null;
    }

    public function attemptMergeLines(string $geometry): ?string
    {
        $row = $this->em
            ->getConnection()
            ->fetchAssociative(
                'SELECT ST_AsGeoJSON(ST_LineMerge(ST_GeomFromGeoJSON(:geom)::geometry)) AS geom',
                [self::GEOM_PARAM => $geometry],
            );

        return $row['geom'] ?? $geometry;
    }

    /**
     * Retourne les polylines CIFS (lat lon lat lon ...) d'une géométrie LineString ou MultiLineString.
     * ST_Multi() normalise en MultiLineString, donc les deux types sont gérés. PostGIS assure la déduplication
     * des segments identiques et ST_LineMerge chaîne les segments connectés ; les tronçons obtenus sont ensuite
     * regroupés et parcourus par le PolylineBuilder (une polyline par groupe de tronçons connectés).
     */
    public function getPolylines(string $geometry): array
    {
        $rows = $this->em
            ->getConnection()
            ->fetchAllAssociative(
                'WITH base AS (
                    SELECT ST_GeomFromGeoJSON(:geom)::geometry AS g
                ),
                dumped AS (
                    SELECT (ST_Dump(ST_Multi(g))).geom AS geom FROM base
                ),
                lines_only AS (
                    SELECT geom FROM dumped
                    WHERE ST_Dimension(geom) = 1 AND ST_NPoints(geom) >= 2
                ),
                deduped AS (
                    SELECT geom FROM (
                        SELECT geom, ROW_NUMBER() OVER (PARTITION BY ST_AsText(geom)) AS rn
                        FROM lines_only
                    ) t WHERE rn = 1
                ),
                merged AS (
                    SELECT ST_LineMerge(ST_Collect(geom)) AS geom FROM deduped
                ),
                dumped_merged AS (
                    SELECT d.path AS path, d.geom AS geom FROM merged, ST_Dump(merged.geom) AS d
                )
                SELECT json_agg(ST_Y(p.geom)::text || \' \' || ST_X(p.geom)::text ORDER BY p.path) AS points
                FROM dumped_merged dm, ST_DumpPoints(dm.geom) AS p
                GROUP BY dm.path
                ORDER BY dm.path',
                [self::GEOM_PARAM => $geometry],
            );

        $lines = [];

        foreach ($rows as $row) {
            $lines[] = json_decode($row['points'], true);
        }

        return $this->polylineBuilder->build($lines);
    }
}
