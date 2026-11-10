<?php

declare(strict_types=1);

namespace App\Domain\Geography;

class GeoJSON
{
    /**
     * @param Coordinates[] $points
     */
    public static function toLineString(array $points): string
    {
        $coordinates = [];

        foreach ($points as $point) {
            $coordinates[] = [$point->longitude, $point->latitude];
        }

        return json_encode(
            [
                'type' => 'LineString',
                'coordinates' => $coordinates,
            ],
        );
    }

    public static function isEmptyGeometryCollection(string $geometry): bool
    {
        $data = json_decode($geometry, true);

        return \is_array($data)
            && ($data['type'] ?? null) === 'GeometryCollection'
            && empty($data['geometries']);
    }

    /**
     * Premier et dernier sommet d'une géométrie linéaire (LineString, MultiLineString ou collection),
     * dans l'ordre des coordonnées. Null si la géométrie compte moins de deux sommets.
     *
     * @return array{Coordinates, Coordinates}|null
     */
    public static function endpoints(string $geometry): ?array
    {
        $data = json_decode($geometry, true);
        $points = \is_array($data) ? self::flattenPoints($data) : [];

        if (\count($points) < 2) {
            return null;
        }

        return [
            Coordinates::fromLonLat($points[0][0], $points[0][1]),
            Coordinates::fromLonLat($points[\count($points) - 1][0], $points[\count($points) - 1][1]),
        ];
    }

    /**
     * @return array<array{float, float}>
     */
    private static function flattenPoints(array $data): array
    {
        return match ($data['type'] ?? null) {
            'Point' => [$data['coordinates']],
            'LineString', 'MultiPoint' => $data['coordinates'],
            'MultiLineString' => array_merge(...($data['coordinates'] ?: [[]])),
            'GeometryCollection' => array_merge(...array_map(self::flattenPoints(...), $data['geometries'] ?: [[]])),
            default => [],
        };
    }

    public static function toPoint(Coordinates $point, bool $includeCrs = false): string
    {
        $geometry = [
            'type' => 'Point',
            'coordinates' => [$point->longitude, $point->latitude],
        ];

        if ($includeCrs) {
            $geometry['crs'] = [
                'type' => 'name',
                'properties' => ['name' => 'EPSG:4326'],
            ];
        }

        return json_encode($geometry);
    }
}
