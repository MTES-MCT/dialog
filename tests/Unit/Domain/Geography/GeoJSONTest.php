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

    public function testIsEmptyGeometryCollection(): void
    {
        $this->assertTrue(GeoJSON::isEmptyGeometryCollection('{"type":"GeometryCollection","geometries":[]}'));

        $this->assertFalse(GeoJSON::isEmptyGeometryCollection('{"type":"GeometryCollection","geometries":[{"type":"Point","coordinates":[-1.9,43.6]}]}'));
        $this->assertFalse(GeoJSON::isEmptyGeometryCollection('{"type":"MultiLineString","coordinates":[]}'));
        $this->assertFalse(GeoJSON::isEmptyGeometryCollection('pas du JSON'));
    }

    public function testEndpoints(): void
    {
        $this->assertEquals(
            [Coordinates::fromLonLat(1, 1), Coordinates::fromLonLat(7, 2)],
            GeoJSON::endpoints('{"type":"LineString","coordinates":[[1,1],[1,2],[7,2]]}'),
        );
        $this->assertEquals(
            [Coordinates::fromLonLat(1, 1), Coordinates::fromLonLat(9, 9)],
            GeoJSON::endpoints('{"type":"MultiLineString","coordinates":[[[1,1],[2,2]],[[8,8],[9,9]]]}'),
        );
        $this->assertEquals(
            [Coordinates::fromLonLat(1, 1), Coordinates::fromLonLat(5, 5)],
            GeoJSON::endpoints('{"type":"GeometryCollection","geometries":[{"type":"LineString","coordinates":[[1,1],[2,2]]},{"type":"LineString","coordinates":[[4,4],[5,5]]}]}'),
        );

        $this->assertNull(GeoJSON::endpoints('{"type":"Point","coordinates":[1,1]}'));
        $this->assertNull(GeoJSON::endpoints('{"type":"Polygon","coordinates":[[[0,0],[1,0],[1,1],[0,0]]]}'));
        $this->assertNull(GeoJSON::endpoints('pas du JSON'));
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
