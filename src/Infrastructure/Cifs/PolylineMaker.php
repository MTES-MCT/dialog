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

    public function getPolylines(string $geometry): array
    {
        $isMultiLineString = str_contains($geometry, 'MultiLineString');

        $rows = $this->em
            ->getConnection()
            ->fetchAllAssociative(
                sprintf(
                    'WITH linestring AS (
                        -- Split the geometry into its individual LINESTRING components
                        SELECT (components.dump).geom AS geom FROM (
                            SELECT (%s) as dump
                        ) AS components
                    )
                    SELECT array_to_string(
                        array(
                            SELECT ST_Y(d.geom) || \' \' || ST_X(d.geom)
                            FROM ST_DumpPoints(linestring.geom) AS d
                        ),
                        \' \'
                    ) AS polyline
                    FROM linestring',
                    $isMultiLineString ? 'ST_Dump(:geom)' : 'ST_Dump(ST_LineMerge(:geom))',
                ),
                ['geom' => sprintf($geometry)],
            );

        $polylines = [];

        foreach ($rows as $row) {
            $polylines[] = $row['polyline'];
        }

        return $polylines;
    }
}
