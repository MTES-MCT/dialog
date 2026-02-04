<?php

declare(strict_types=1);

namespace App\Infrastructure\Cifs;

use App\Application\Cifs\PolylineMakerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class PolylineMaker implements PolylineMakerInterface
{
    private const GEOM_PARAM = 'geom';

    public function __construct(
        private EntityManagerInterface $em,
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
     * Retourne une seule polyline CIFS (lat lon lat lon ...) à partir d'une géométrie LineString ou MultiLineString.
     * ST_Multi() normalise en MultiLineString, donc les deux types sont gérés. PostGIS assure la déduplication
     * des segments identiques, ST_LineMerge chaîne les segments connectés, puis dump des points.
     */
    public function getMergedPolyline(string $geometry): string
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
                    SELECT (ST_Dump(geom)).geom AS geom FROM merged WHERE geom IS NOT NULL
                )
                SELECT COALESCE(
                    array_to_string(
                        array_agg(
                            array_to_string(
                                (SELECT array_agg(ST_Y(d.geom)::text || \' \' || ST_X(d.geom)::text ORDER BY (d.path))
                                 FROM ST_DumpPoints(dm.geom) AS d),
                                \' \'
                            )
                        ),
                        \' \'
                    ),
                    \'\'
                ) AS polyline
                FROM dumped_merged dm',
                [self::GEOM_PARAM => $geometry],
            );

        $polyline = $row['polyline'] ?? '';

        return \is_string($polyline) ? trim($polyline) : '';
    }
}
