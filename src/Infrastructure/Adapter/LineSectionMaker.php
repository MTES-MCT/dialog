<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\DepartmentalRoadGeocodingFailureException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\LineSectionMakerInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

final class LineSectionMaker implements LineSectionMakerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws GeocodingFailureException|DepartmentalRoadGeocodingFailureException
     */
    public function computeSection(
        RoadTypeEnum $roadType,
        string $lineGeometry,
        Coordinates $fromCoords,
        Coordinates $toCoords,
    ): string {
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
            -- For each point, find the LINESTRING(s) it is closest to (multiple closest line strings is a mostly theoretical case)
            linestring_a AS (
                SELECT l.geom
                FROM linestring AS l
                WHERE ST_Distance(:point_a, l.geom) = (SELECT MIN(ST_Distance(:point_a, geom)) FROM linestring)
            ),
            linestring_b AS (
                SELECT l.geom
                FROM linestring AS l
                WHERE ST_Distance(:point_b, l.geom) = (SELECT MIN(ST_Distance(:point_b, geom)) FROM linestring)
            )
            -- Compute the section on the LINESTRING to which both points belong to.
            SELECT ST_AsGeoJSON(
                ST_LineSubstring(
                    l.geom,
                    LEAST(ST_LineLocatePoint(l.geom, :point_a), ST_LineLocatePoint(l.geom, :point_b)),
                    GREATEST(ST_LineLocatePoint(l.geom, :point_a), ST_LineLocatePoint(l.geom, :point_b))
                )
            ) AS section
            FROM linestring_a AS l
            -- If the points belong to different LINESTRINGs, this join will be empty.
            INNER JOIN linestring_b ON l.geom = linestring_b.geom
            LIMIT 1
            ',
            [
                'geom' => $lineGeometry,
                'point_a' => $pointA,
                'point_b' => $pointB,
            ],
        );

        if (!$row) {
            $msg = sprintf(
                'failed to find common linestring for points %s and %s on line %s',
                $pointA,
                $pointB,
                $lineGeometry,
            );

            if (RoadTypeEnum::DEPARTMENTAL_ROAD === $roadType) {
                throw new DepartmentalRoadGeocodingFailureException($msg);
            } else {
                throw new GeocodingFailureException($msg);
            }
        }

        return $row['section'];
    }
}
