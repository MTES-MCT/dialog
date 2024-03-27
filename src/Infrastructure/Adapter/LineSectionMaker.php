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
    ): ?string {
        $includeCrs = str_contains($lineGeometry, '"crs"');

        $pointA = $fromCoords->asGeoJSON($includeCrs);
        $pointB = $toCoords->asGeoJSON($includeCrs);

        $row = $this->em->getConnection()->fetchAssociative(
            'WITH linestring AS (
                -- Split the line geometry into its individual LINESTRING components
                SELECT (components.dump).geom AS geom FROM (
                    SELECT ST_Dump(ST_LineMerge(:geom)) AS dump
                ) AS components
            ),
            -- For each endpoint, find the individual LINESTRING it is closest to
            linestring_a AS (
                SELECT l.geom
                FROM linestring AS l
                ORDER BY ST_Distance(:point_a, l.geom)
                LIMIT 1
            ),
            linestring_b AS (
                SELECT l.geom
                FROM linestring AS l
                ORDER BY ST_Distance(:point_b, l.geom)
                LIMIT 1
            )
            -- Compute the line substring if and only if both endpoints are on the same LINESTRING,
            -- otherwise return nothing.
            SELECT ST_AsGeoJSON(
                ST_LineSubstring(
                    l.geom,
                    LEAST(ST_LineLocatePoint(l.geom, :point_a), ST_LineLocatePoint(l.geom, :point_b)),
                    GREATEST(ST_LineLocatePoint(l.geom, :point_a), ST_LineLocatePoint(l.geom, :point_b))
                )
            ) AS section
            FROM linestring_a AS l
            INNER JOIN linestring_b ON l.geom = linestring_b.geom
            ',
            [
                'geom' => $lineGeometry,
                'point_a' => $pointA,
                'point_b' => $pointB,
            ],
        );

        return $row ? $row['section'] : null;
    }
}
