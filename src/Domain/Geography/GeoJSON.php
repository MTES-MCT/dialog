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

    /**
     * @return Coordinates[]
     */
    public static function parseLineString(string $geometry): array
    {
        $points = [];
        $geom = json_decode($geometry, associative: true);

        foreach ($geom['coordinates'] as $coords) {
            $points[] = Coordinates::fromLonLat($coords[0], $coords[1]);
        }

        return $points;
    }
}
