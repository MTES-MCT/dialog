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

        $row = $this->em->getConnection()->fetchAssociative(
            'WITH a AS (
                SELECT ST_SetSRID(ST_GeomFromGeoJSON(:point_a), 4326) AS geom
            ),
            b AS (
                SELECT ST_SetSRID(ST_GeomFromGeoJSON(:point_b), 4326) AS geom
            ),
            merged_linestring AS (
                -- Split the line geometry into its individual merged LINESTRING components
                SELECT (components.dump).geom AS geom FROM (
                    SELECT ST_Dump(ST_LineMerge(ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326))) AS dump
                ) AS components
            ),
            -- For each point, find the merged LINESTRING(s) it is closest to, within a tolerance radius
            merged_linestring_a AS (
                SELECT l.geom
                FROM merged_linestring AS l
                JOIN a ON true
                -- ::geography ensures distances are computed in meters
                WHERE ST_Distance(a.geom::geography, l.geom::geography) <= (SELECT MIN(ST_Distance(a.geom::geography, geom::geography)) FROM merged_linestring) + :tolerance
            ),
            merged_linestring_b AS (
                SELECT l.geom
                FROM merged_linestring AS l
                JOIN b ON true
                -- ::geography ensures distances are computed in meters
                WHERE ST_Distance(b.geom::geography, l.geom::geography) <= (SELECT MIN(ST_Distance(b.geom::geography, geom::geography)) FROM merged_linestring) + :tolerance
            ),
            merged_section AS (
                -- Compute the section on the merged LINESTRING to which both points belong to.
                SELECT ST_LineSubstring(
                    l.geom,
                    LEAST(ST_LineLocatePoint(l.geom, a.geom), ST_LineLocatePoint(l.geom, b.geom)),
                    GREATEST(ST_LineLocatePoint(l.geom, a.geom), ST_LineLocatePoint(l.geom, b.geom))
                ) AS geom
                FROM merged_linestring_a AS l
                JOIN a ON true
                JOIN b ON true
                -- If the points belong to different LINESTRINGs, this join will be empty.
                INNER JOIN merged_linestring_b ON l.geom = merged_linestring_b.geom
                -- When a and b map to the same point on line, ST_LineSubstring returns a POINT, which
                -- is not a section and should yield an error.
                WHERE ST_LineLocatepoint(l.geom, a.geom) <> ST_LineLocatePoint(l.geom, b.geom)
                LIMIT 1
            ),
            -- Split back at original endpoints
            linestring AS (
                -- Split the line geometry into its individual LINESTRING components
                SELECT (components.dump).geom AS geom FROM (
                    SELECT ST_Dump(ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326))) AS dump
                ) AS components
            ),
            endpoints AS (
                SELECT ST_Union(
                    ST_Union(
                        ST_StartPoint(l.geom),
                        ST_EndPoint(l.geom)
                    )
                ) AS geom
                FROM linestring AS l
            )
            SELECT ST_AsGeoJSON(
                ST_Split(
                    -- ST_Snap avoids intersection issues due to numerical rounding errors
                    -- https://postgis.net/docs/ST_Split.html
                    ST_Snap(
                        s.geom,
                        endpoints.geom,
                        -- Tolerance is in degrees (EPSG:4326): 0.00001° ~= 1m
                        0.00001
                    ),
                    endpoints.geom
                )
            ) AS geom
            FROM merged_section AS s
            JOIN endpoints on true
            ',
            [
                'geom' => $lineGeometry,
                'point_a' => $pointA,
                'point_b' => $pointB,
                'tolerance' => $tolerance,
            ],
        );

        if (!$row) {
            $msg = \sprintf(
                'failed to find common linestring for points %s and %s on line %s',
                $pointA,
                $pointB,
                $lineGeometry,
            );

            throw new GeocodingFailureException($msg);
        }

        return $row['geom'];
    }
}
