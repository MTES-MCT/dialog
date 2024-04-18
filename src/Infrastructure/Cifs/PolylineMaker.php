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

    public function getPolylines(string $geometry, ?string $roadGeometry = null, int $pointBufferSize = 35): array
    {
        $rows = $this->em
            ->getConnection()
            ->fetchAllAssociative(
                'WITH buffered AS (
                    SELECT (
                        CASE
                        -- Waze requires at least 2 points, and a linestring length of at least 35 meters.
                        -- In case the geometry amounts to a single point (for example noEntry on a single house number),
                        -- we take a buffer of the road around that point.
                        WHEN (SELECT COUNT(DISTINCT d.geom) FROM ST_DumpPoints(:geom::geometry) AS d) = 1
                            THEN ST_LineMerge(ST_Intersection(
                                ST_SetSRID(:road_geometry::geometry, 4326),
                                ST_Transform(
                                    ST_Buffer(
                                        ST_Transform(
                                            ST_SetSRID(ST_GeometryN(:geom::geometry, 1), 4326),
                                            2154
                                        ),
                                        :point_buffer_size
                                    ),
                                    4326
                                )
                            ))
                        ELSE
                            :geom
                        END
                    ) AS geom
                ), linestring AS (
                    -- Split the geometry into its individual LINESTRING components
                    SELECT (components.dump).geom AS geom FROM (
                        SELECT ST_Dump(CASE WHEN ST_NumGeometries(ST_LineMerge(b.geom)) = 0 THEN geom ELSE ST_LineMerge(b.geom) END) AS dump
                        FROM buffered AS b
                    ) AS components
                )
                SELECT array_to_string(
                    array(
                        SELECT ST_Y(p.geom) || \' \' || ST_X(p.geom)
                        FROM ST_DumpPoints(l.geom) AS p
                    ),
                    \' \'
                ) AS polyline
                FROM linestring AS l
                WHERE (SELECT COUNT(*) FROM ST_DumpPoints(l.geom)) > 0
                ',
                [
                    'geom' => $geometry,
                    'road_geometry' => $roadGeometry,
                    'point_buffer_size' => $pointBufferSize,
                ],
            );

        $polylines = [];

        foreach ($rows as $row) {
            $polylines[] = $row['polyline'];
        }

        return $polylines;
    }
}
