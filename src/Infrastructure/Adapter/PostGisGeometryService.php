<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\GeometryServiceInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use Doctrine\ORM\EntityManagerInterface;

final class PostGisGeometryService implements GeometryServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function locatePointOnLine(Coordinates $point, string $geometry): Coordinates
    {
        $sql = 'SELECT
            ST_X(ST_ClosestPoint(:geom, :pt)) AS x,
            ST_Y(ST_ClosestPoint(:geom, :pt)) AS y
        ';

        $params = [
            'geom' => $geometry,
            'pt' => GeoJSON::toPoint($point),
        ];

        $stmt = $this->em->getConnection()->prepare($sql);
        $row = $stmt->execute($params)->fetchAssociative();

        return Coordinates::fromLonLat((float) $row['x'], (float) $row['y']);
    }

    public function clipLine(string $lineGeometry, Coordinates|null $start, Coordinates|null $end): string
    {
        $sql = sprintf(
            'SELECT ST_AsGeoJSON(ST_LineSubstring(ST_LineMerge(:geom), %s, %s)) AS result',
            $start ? 'ST_LineLocatePoint(ST_LineMerge(:geom), :start_pt)' : '0',
            $end ? 'ST_LineLocatePoint(ST_LineMerge(:geom), :end_pt)' : '1',
        );

        $params = [
            'geom' => $lineGeometry,
        ];

        if ($start) {
            $params['start_pt'] = GeoJSON::toPoint($start);
        }

        if ($end) {
            $params['end_pt'] = GeoJSON::toPoint($end);
        }

        $stmt = $this->em->getConnection()->prepare($sql);
        $row = $stmt->execute($params)->fetchAssociative();

        return $row['result'];
    }
}
