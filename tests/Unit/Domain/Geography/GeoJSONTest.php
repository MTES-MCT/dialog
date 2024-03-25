<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Geography;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use PHPUnit\Framework\TestCase;

final class GeoJSONTest extends TestCase
{
    public function testToLineString(): void
    {
        $result = GeoJSON::toLineString([Coordinates::fromLonLat(-1.9, 43.6), Coordinates::fromLonLat(0.4, 42.3)]);

        $this->assertSame(
            json_encode(
                [
                    'type' => 'LineString',
                    'coordinates' => [
                        [-1.9, 43.6],
                        [0.4, 42.3],
                    ],
                ],
            ),
            $result,
        );
    }

    public function testToPoint(): void
    {
        $this->assertSame(
            json_encode(
                [
                    'type' => 'Point',
                    'coordinates' => [-1.9, 43.6],
                ],
            ),
            GeoJSON::toPoint(Coordinates::fromLonLat(-1.9, 43.6)),
        );

        $this->assertSame(
            json_encode(
                [
                    'type' => 'Point',
                    'coordinates' => [-1.9, 43.6],
                    'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
                ],
            ),
            GeoJSON::toPoint(Coordinates::fromLonLat(-1.9, 43.6), includeCrs: true),
        );
    }
}
