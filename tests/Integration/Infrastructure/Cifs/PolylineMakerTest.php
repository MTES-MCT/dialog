<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Cifs;

use App\Application\Cifs\PolylineMakerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PolylineMakerTest extends WebTestCase
{
    /** @var PolylineMakerInterface */
    private $polylineMaker;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->polylineMaker = $container->get(PolylineMakerInterface::class);
    }

    private function provideConvertToPolylines(): array
    {
        return [
            'empty' => [
                'geometry' => '{"type": "LineString", "coordinates": []}',
                'polylines' => [],
            ],
            'line' => [
                'geometry' => '{"type": "LineString", "coordinates": [[0, 1], [2, 3]]}',
                'polylines' => ['1 0 3 2'],
            ],
            'multiline-mergeable' => [
                'geometry' => '{"type": "MultiLineString", "coordinates": [[[0, 1], [2, 3]], [[2, 3], [4, 5]]]}',
                'polylines' => ['1 0 3 2 5 4'],
            ],
            'multiline-separate' => [
                'geometry' => '{"type": "MultiLineString", "coordinates": [[[0, 1], [2, 3]], [[4, 5], [6, 7]]]}',
                'polylines' => ['1 0 3 2', '5 4 7 6'],
            ],
            'point' => [
                'geometry' => '{"type": "Point", "coordinates": [0, 1]}',
                'polylines' => [],
            ],
            'line-actually-point' => [
                'geometry' => '{"type": "LineString", "coordinates": [[0, 1]]}',
                'polylines' => [],
            ],
            'point-road-buffer' => [
                // Use realistic EPSG:4326 points
                'geometry' => '{"type": "Point", "coordinates": [2, 45]}',
                'polylines' => ['44.999997719 1.999746211 45.00000398899917 2.0004441302650258'],
                // -20m West, +50m East
                'roadGeometry' => '{"type": "LineString", "coordinates": [[1.999746211,44.999997719], [2.000634472,45.000005699]]}',
            ],
            'line-actually-duplicate-point-road-buffer' => [
                'geometry' => '{"type": "LineString", "coordinates": [[2, 45], [2, 45]]}',
                'polylines' => ['44.999997719 1.999746211 45.00000398899917 2.0004441302650258'],
                'roadGeometry' => '{"type": "LineString", "coordinates": [[1.999746211,44.999997719], [2.000634472,45.000005699]]}',
            ],
        ];
    }

    /**
     * @dataProvider provideConvertToPolylines
     */
    public function testConvertToPolylines(string $geometry, array $polylines, ?string $roadGeometry = null): void
    {
        $this->assertEquals($polylines, $this->polylineMaker->getPolylines($geometry, $roadGeometry));
    }
}
