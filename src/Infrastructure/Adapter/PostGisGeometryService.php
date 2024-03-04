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
        // Notation: t = Number between 0 (first point) and 1 (last point), aka "curvilinear abscissa"
        $pointStmt = $this->em->getConnection()->prepare('SELECT ST_LineLocatePoint(ST_LineMerge(:geom), :pt) AS t');

        $tStart = $start
            ? (float) $pointStmt->execute([
                'geom' => $lineGeometry,
                'pt' => GeoJSON::toPoint($start),
            ])->fetchAssociative()['t']
            : 0;

        $tEnd = $end
            ? (float) $pointStmt->execute([
                'geom' => $lineGeometry,
                'pt' => GeoJSON::toPoint($end),
            ])->fetchAssociative()['t']
            : 1;

        if ($tStart > $tEnd) {
            [$tStart, $tEnd] = [$tEnd, $tStart];
        }

        $sql = sprintf(
            'SELECT ST_AsGeoJSON(ST_LineSubstring(ST_LineMerge(:geom), %.6f, %.6f)) AS result',
            $tStart,
            $tEnd,
        );

        $params = [
            'geom' => $lineGeometry,
        ];

        $stmt = $this->em->getConnection()->prepare($sql);
        $row = $stmt->execute($params)->fetchAssociative();

        return $row['result'];
    }
}
