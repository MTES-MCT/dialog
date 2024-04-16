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
        $rows = $this->em
            ->getConnection()
            ->fetchAllAssociative(
                'WITH linestring AS (
                        -- Split the geometry into its individual LINESTRING components
                        SELECT (components.dump).geom AS geom FROM (
                            SELECT ST_Dump(ST_LineMerge(:geom)) AS dump
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
                ['geom' => $geometry],
            );

        return array_map(fn ($row) => $row['polyline'], $rows);
    }
}
