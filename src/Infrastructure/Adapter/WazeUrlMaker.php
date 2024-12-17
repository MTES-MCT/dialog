<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Regulation\View\Measure\MeasureView;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class WazeUrlMaker
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param MeasureView[] $measures
     */
    public function makeAll(array $measures): array
    {
        $urlsByMeasureAndLocation = [];
        $hashes = [];
        $geometries = [];

        foreach ($measures as $measure) {
            $urlsByMeasureAndLocation[$measure->uuid] = [];

            foreach ($measure->locations as $location) {
                $hashes[] = implode('#', [$measure->uuid, $location->uuid]);
                $geometries[] = $location->geometry;
            }
        }

        // Use a single SQL query
        $urls = $this->bulkBuildUrls($geometries);

        foreach ($urls as $index => $url) {
            $hash = $hashes[$index];
            [$measureUuid, $locationUuid] = explode('#', $hash);
            $urlsByMeasureAndLocation[$measureUuid][$locationUuid] = $url;
        }

        return $urlsByMeasureAndLocation;
    }

    private function bulkBuildUrls(array $geometries): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT ST_AsGeoJSON(ST_Centroid(g)) AS geom
            FROM unnest(ARRAY[:geometries]) AS g',
            ['geometries' => $geometries],
            ['geometries' => ArrayParameterType::STRING],
        );

        $urls = [];

        foreach ($rows as $row) {
            $lonLat = json_decode($row['geom'], true)['coordinates'];

            $urls[] = 'https://www.waze.com/en/live-map/directions?' . http_build_query([
                'latlng' => \sprintf('%.6f,%.6f', $lonLat[1], $lonLat[0]),
            ]);
        }

        return $urls;
    }
}
