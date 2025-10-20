<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\LineSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use Doctrine\ORM\EntityManagerInterface;

final class LineSectionMaker implements LineSectionMakerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws GeocodingFailureException
     */
    public function computeSection(
        string $lineGeometry,
        Coordinates $fromCoords,
        Coordinates $toCoords,
        int|float $tolerance = 1, // Meters
    ): string {
        $includeCrs = str_contains($lineGeometry, '"crs"');

        $pointA = $fromCoords->asGeoJSON($includeCrs);
        $pointB = $toCoords->asGeoJSON($includeCrs);

        // Convertir la tolérance (mètres) en degrés (EPSG:4326) pour les opérations de snapping/splitting
        $snapToleranceDegrees = 0.00001 * (float) $tolerance;

        $row = $this->em->getConnection()->fetchAssociative(
            'WITH a AS (
                    SELECT ST_SetSRID(ST_GeomFromGeoJSON(:point_a), 4326) AS geom
                ),
                b AS (
                    SELECT ST_SetSRID(ST_GeomFromGeoJSON(:point_b), 4326) AS geom
                ),
                base_geom AS (
                    SELECT ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326) AS geom
                ),
                -- Split the base geometry into LINESTRING components (original graph)
                linestring AS (
                    SELECT (components.dump).geom AS geom FROM (
                        SELECT ST_Dump(ST_Multi((SELECT geom FROM base_geom))) AS dump
                    ) AS components
                ),
                -- Work on merged components of the original geometry first
                base_merged_linestring AS (
                    SELECT (components.dump).geom AS geom FROM (
                        SELECT ST_Dump(ST_LineMerge((SELECT geom FROM base_geom))) AS dump
                    ) AS components
                ),
                -- Identify the closest merged linestring to each point (host segments)
                a_host AS (
                    SELECT l.geom
                    FROM base_merged_linestring l, a
                    ORDER BY ST_Distance(a.geom::geography, l.geom::geography)
                    LIMIT 1
                ),
                b_host AS (
                    SELECT l.geom
                    FROM base_merged_linestring l, b
                    ORDER BY ST_Distance(b.geom::geography, l.geom::geography)
                    LIMIT 1
                ),
                -- Build endpoints of each host linestring
                a_endpoints AS (
                    SELECT ST_Union(ST_StartPoint(geom), ST_EndPoint(geom)) AS geom FROM a_host
                ),
                b_endpoints AS (
                    SELECT ST_Union(ST_StartPoint(geom), ST_EndPoint(geom)) AS geom FROM b_host
                ),
                -- Shortest connector between the two host linestrings endpoints
                connector AS (
                    SELECT ST_ShortestLine(a_endpoints.geom, b_endpoints.geom) AS geom FROM a_endpoints, b_endpoints
                ),
                -- Determine if both points are on the same merged linestring
                merged_linestring AS (
                    SELECT (components.dump).geom AS geom FROM (
                        SELECT ST_Dump(ST_Collect(geom)) AS dump FROM base_merged_linestring
                    ) AS components
                ),
                merged_linestring_a AS (
                    SELECT l.geom
                    FROM merged_linestring AS l
                    JOIN a ON true
                    WHERE ST_DWithin(a.geom::geography, l.geom::geography, (SELECT MIN(ST_Distance(a.geom::geography, geom::geography)) FROM merged_linestring) + :tolerance)
                ),
                merged_linestring_b AS (
                    SELECT l.geom
                    FROM merged_linestring AS l
                    JOIN b ON true
                    WHERE ST_DWithin(b.geom::geography, l.geom::geography, (SELECT MIN(ST_Distance(b.geom::geography, geom::geography)) FROM merged_linestring) + :tolerance)
                ),
                same_host_section AS (
                    SELECT ST_LineSubstring(
                        l.geom,
                        LEAST(ST_LineLocatePoint(l.geom, a.geom), ST_LineLocatePoint(l.geom, b.geom)),
                        GREATEST(ST_LineLocatePoint(l.geom, a.geom), ST_LineLocatePoint(l.geom, b.geom))
                    ) AS geom
                    FROM merged_linestring_a AS l
                    JOIN merged_linestring_b AS r ON l.geom = r.geom
                    JOIN a ON true
                    JOIN b ON true
                    LIMIT 1
                ),
                -- If not on the same host, cut two proper sections towards the closest ENDPOINTS
                a_start AS (SELECT ST_StartPoint(geom) AS geom FROM a_host),
                a_end   AS (SELECT ST_EndPoint(geom)   AS geom FROM a_host),
                b_start AS (SELECT ST_StartPoint(geom) AS geom FROM b_host),
                b_end   AS (SELECT ST_EndPoint(geom)   AS geom FROM b_host),
                a_to_b_endpoint AS (
                    SELECT CASE WHEN ST_Distance((SELECT geom FROM a_start)::geography, (SELECT geom FROM b_host)::geography)
                                      <= ST_Distance((SELECT geom FROM a_end)::geography,   (SELECT geom FROM b_host)::geography)
                                THEN (SELECT geom FROM a_start)
                                ELSE (SELECT geom FROM a_end)
                           END AS geom
                ),
                b_to_a_endpoint AS (
                    SELECT CASE WHEN ST_Distance((SELECT geom FROM b_start)::geography, (SELECT geom FROM a_host)::geography)
                                      <= ST_Distance((SELECT geom FROM b_end)::geography,   (SELECT geom FROM a_host)::geography)
                                THEN (SELECT geom FROM b_start)
                                ELSE (SELECT geom FROM b_end)
                           END AS geom
                ),
                a_side_section AS (
                    SELECT ST_LineSubstring(
                        (SELECT geom FROM a_host),
                        LEAST(ST_LineLocatePoint((SELECT geom FROM a_host), (SELECT geom FROM a)), ST_LineLocatePoint((SELECT geom FROM a_host), (SELECT geom FROM a_to_b_endpoint))),
                        GREATEST(ST_LineLocatePoint((SELECT geom FROM a_host), (SELECT geom FROM a)), ST_LineLocatePoint((SELECT geom FROM a_host), (SELECT geom FROM a_to_b_endpoint)))
                    ) AS geom
                ),
                b_side_section AS (
                    SELECT ST_LineSubstring(
                        (SELECT geom FROM b_host),
                        LEAST(ST_LineLocatePoint((SELECT geom FROM b_host), (SELECT geom FROM b)), ST_LineLocatePoint((SELECT geom FROM b_host), (SELECT geom FROM b_to_a_endpoint))),
                        GREATEST(ST_LineLocatePoint((SELECT geom FROM b_host), (SELECT geom FROM b)), ST_LineLocatePoint((SELECT geom FROM b_host), (SELECT geom FROM b_to_a_endpoint)))
                    ) AS geom
                )
                SELECT CASE WHEN EXISTS (SELECT 1 FROM same_host_section)
                    THEN ST_AsGeoJSON(f_ST_NormalizeGeometryCollection((SELECT geom FROM same_host_section)))
                    ELSE ST_AsGeoJSON(f_ST_NormalizeGeometryCollection(ST_Collect((SELECT geom FROM a_side_section), (SELECT geom FROM b_side_section))))
                END AS geom
                ',
            [
                'geom' => $lineGeometry,
                'point_a' => $pointA,
                'point_b' => $pointB,
                'tolerance' => $tolerance,
                'snap_deg_tol' => $snapToleranceDegrees,
            ],
        );
        if ($row && isset($row['geom'])) {
            return $row['geom'];
        }

        $msg = \sprintf(
            'failed to find common linestring for points %s and %s on line %s',
            $pointA,
            $pointB,
            $lineGeometry,
        );

        throw new GeocodingFailureException($msg);
    }
}
