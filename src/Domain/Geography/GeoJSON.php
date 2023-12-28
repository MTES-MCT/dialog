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
    public static function parseLineString(string|array $geometry): array
    {
        if (\is_string($geometry)) {
            $geometry = json_decode($geometry, associative: true);
        }

        $points = [];

        foreach ($geometry['coordinates'] as $coords) {
            $points[] = Coordinates::fromLonLat($coords[0], $coords[1]);
        }

        return $points;
    }
}
