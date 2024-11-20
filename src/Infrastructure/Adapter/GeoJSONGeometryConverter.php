<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use Doctrine\DBAL\Connection;

final class GeoJSONGeometryConverter
{
    private const GEOMETRY_QUERY = 'SELECT ST_GeomFromGeoJSON(:value)';
    private const FEATURE_QUERY = "SELECT ST_GeomFromGeoJSON(:value::json->'geometry')";
    private const FEATURE_COLLECTION_QUERY =
        "SELECT ST_Collect(ST_GeomFromGeoJSON(feat->'geometry')
        FROM (
            SELECT json_array_elements((:value)::json->'features') AS feat
        ) AS f
        "
    ;

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function isValid(string $value): bool
    {
        if ($this->succeeds(self::FEATURE_COLLECTION_QUERY, ['value' => $value])) {
            return true;
        }

        if ($this->succeeds(self::FEATURE_QUERY, ['value' => $value])) {
            return true;
        }

        if ($this->succeeds(self::GEOMETRY_QUERY, ['value' => $value])) {
            return true;
        }

        return false;
    }

    public function convertToGeometry(string $value): string
    {
        $json = json_decode($value, true);

        // FeatureCollection
        if (!empty($json['features'])) {
            $geometries = [];

            foreach ($json['features'] as $feat) {
                $geometries[] = $feat['geometry'];
            }

            return json_encode(['type' => 'GeometryCollection', 'geometries' => $geometries]);
        }

        // Feature
        if (!empty($json['geometry'])) {
            return json_encode($json['geometry']);
        }

        // Other cases: already a Geometry
        return $value;
    }

    private function succeeds(string $query, array $params): bool
    {
        try {
            $this->connection->fetchAssociative($query, $params);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
