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
            linestring AS (
                -- Split the line geometry into its individual LINESTRING components
                SELECT (components.dump).geom AS geom FROM (
                    SELECT ST_Dump(ST_LineMerge(ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326))) AS dump
                ) AS components
            ),
            -- For each point, find the LINESTRING(s) it is closest to, within a tolerance radius
            linestring_a AS (
                SELECT l.geom
                FROM linestring AS l
                JOIN a ON true
                -- ::geography ensures distances are computed in meters
                WHERE ST_Distance(a.geom::geography, l.geom::geography) <= (SELECT MIN(ST_Distance(a.geom::geography, geom::geography)) FROM linestring) + :tolerance
            ),
            linestring_b AS (
                SELECT l.geom
                FROM linestring AS l
                JOIN b ON true
                -- ::geography ensures distances are computed in meters
                WHERE ST_Distance(b.geom::geography, l.geom::geography) <= (SELECT MIN(ST_Distance(b.geom::geography, geom::geography)) FROM linestring) + :tolerance
            )
            -- Compute the section on the LINESTRING to which both points belong to.
            SELECT ST_AsGeoJSON(
                ST_LineSubstring(
                    l.geom,
                    LEAST(ST_LineLocatePoint(l.geom, a.geom), ST_LineLocatePoint(l.geom, b.geom)),
                    GREATEST(ST_LineLocatePoint(l.geom, a.geom), ST_LineLocatePoint(l.geom, b.geom))
                )
            ) AS section
            FROM linestring_a AS l
            JOIN a ON true
            JOIN b ON true
            -- If the points belong to different LINESTRINGs, this join will be empty.
            INNER JOIN linestring_b ON l.geom = linestring_b.geom
            LIMIT 1
            ',
            [
                'geom' => $lineGeometry,
                'point_a' => $pointA,
                'point_b' => $pointB,
                'tolerance' => $tolerance,
            ],
        );

        if (!$row) {
            $msg = sprintf(
                'failed to find common linestring for points %s and %s on line %s',
                $pointA,
                $pointB,
                $lineGeometry,
            );

            throw new GeocodingFailureException($msg);
        }

        return $row['section'];
    }
}
