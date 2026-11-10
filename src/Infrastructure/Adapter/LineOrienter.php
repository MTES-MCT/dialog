<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\LineOrienterInterface;
use App\Domain\Geography\Coordinates;
use Doctrine\ORM\EntityManagerInterface;

final class LineOrienter implements LineOrienterInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function orient(string $geometry, Coordinates $from, Coordinates $to): string
    {
        $row = $this->em->getConnection()->fetchAssociative(
            'WITH a AS (
                    SELECT ST_SetSRID(ST_GeomFromGeoJSON(:point_a), 4326) AS geom
                ),
                b AS (
                    SELECT ST_SetSRID(ST_GeomFromGeoJSON(:point_b), 4326) AS geom
                ),
                base AS (
                    SELECT ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326) AS geom
                ),
                -- Ne garde que les parties linéaires et fusionne les morceaux connectés en une seule ligne.
                -- Une géométrie sans partie linéaire (points) est conservée telle quelle.
                merged AS (
                    SELECT CASE
                        WHEN ST_GeometryType(geom) IN (\'ST_LineString\', \'ST_MultiLineString\', \'ST_GeometryCollection\')
                             AND NOT ST_IsEmpty(ST_CollectionExtract(geom, 2))
                            THEN ST_LineMerge(ST_CollectionExtract(geom, 2))
                        ELSE geom
                    END AS geom
                    FROM base
                ),
                -- Morceaux non connectés : chacun est orienté pour que son premier sommet soit le plus proche de A
                oriented_pieces AS (
                    SELECT
                        CASE WHEN ST_Distance(ST_StartPoint(p.geom)::geography, a.geom::geography) > ST_Distance(ST_EndPoint(p.geom)::geography, a.geom::geography)
                            THEN ST_Reverse(p.geom)
                            ELSE p.geom
                        END AS geom,
                        p.path
                    FROM merged m, a, LATERAL ST_Dump(m.geom) AS p
                    WHERE ST_GeometryType(m.geom) = \'ST_MultiLineString\'
                ),
                oriented AS (
                    SELECT CASE
                        -- Ligne unique : A doit se situer avant B le long de la ligne
                        WHEN ST_GeometryType(m.geom) = \'ST_LineString\' THEN
                            CASE WHEN ST_LineLocatePoint(m.geom, a.geom) > ST_LineLocatePoint(m.geom, b.geom)
                                THEN ST_Reverse(m.geom)
                                ELSE m.geom
                            END
                        -- Plusieurs morceaux : ordonnés par distance croissante à A
                        WHEN ST_GeometryType(m.geom) = \'ST_MultiLineString\' THEN (
                            SELECT ST_Collect(op.geom ORDER BY
                                ST_Distance(ST_StartPoint(op.geom)::geography, a.geom::geography),
                                ST_Distance(ST_EndPoint(op.geom)::geography, a.geom::geography),
                                op.path
                            )
                            FROM oriented_pieces op
                        )
                        ELSE m.geom
                    END AS geom
                    FROM merged m, a, b
                )
                SELECT ST_AsGeoJSON(f_ST_NormalizeGeometryCollection((SELECT geom FROM oriented))) AS geom',
            [
                'geom' => $geometry,
                'point_a' => $from->asGeoJSON(),
                'point_b' => $to->asGeoJSON(),
            ],
        );

        return $row['geom'] ?? $geometry;
    }

    public function normalize(string $geometry): string
    {
        $row = $this->em->getConnection()->fetchAssociative(
            'WITH base AS (
                    SELECT ST_SetSRID(ST_GeomFromGeoJSON(:geom), 4326) AS geom
                ),
                merged AS (
                    SELECT CASE
                        WHEN ST_GeometryType(geom) IN (\'ST_LineString\', \'ST_MultiLineString\', \'ST_GeometryCollection\')
                             AND NOT ST_IsEmpty(ST_CollectionExtract(geom, 2))
                            THEN ST_LineMerge(ST_CollectionExtract(geom, 2))
                        ELSE NULL
                    END AS geom
                    FROM base
                ),
                -- Extrémités de tous les morceaux
                endpoints AS (
                    SELECT ST_StartPoint(d.geom) AS geom FROM merged m, LATERAL ST_Dump(m.geom) AS d
                    UNION ALL
                    SELECT ST_EndPoint(d.geom) AS geom FROM merged m, LATERAL ST_Dump(m.geom) AS d
                ),
                -- A : la plus à l\'ouest puis au sud ; B : la plus éloignée de A
                a AS (SELECT geom FROM endpoints WHERE geom IS NOT NULL ORDER BY ST_X(geom), ST_Y(geom) LIMIT 1),
                b AS (SELECT e.geom FROM endpoints e, a WHERE e.geom IS NOT NULL ORDER BY ST_Distance(e.geom::geography, a.geom::geography) DESC LIMIT 1)
                SELECT ST_AsGeoJSON(m.geom) AS geom, ST_X(a.geom) AS ax, ST_Y(a.geom) AS ay, ST_X(b.geom) AS bx, ST_Y(b.geom) AS "by"
                FROM merged m, a, b',
            ['geom' => $geometry],
        );

        // Pas de partie linéaire (points, polygone…) : rien à orienter
        if (!$row || $row['geom'] === null || $row['ax'] === null) {
            return $geometry;
        }

        return $this->orient(
            $row['geom'],
            Coordinates::fromLonLat((float) $row['ax'], (float) $row['ay']),
            Coordinates::fromLonLat((float) $row['bx'], (float) $row['by']),
        );
    }
}
