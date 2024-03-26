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
