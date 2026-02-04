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

    public function testGetMergedPolyline(): void
    {
        $geometry = '{"type": "MultiLineString", "coordinates": [[[0, 1], [2, 3]], [[2, 3], [4, 5]]]}';
        $merged = $this->polylineMaker->getMergedPolyline($geometry);
        $this->assertSame('1 0 3 2 5 4', $merged);
    }

    public function testGetMergedPolylineEmpty(): void
    {
        $this->assertSame('', $this->polylineMaker->getMergedPolyline('{"type": "Point", "coordinates": [0, 1]}'));
        $this->assertSame('', $this->polylineMaker->getMergedPolyline('{"type": "LineString", "coordinates": []}'));
    }

    public function testNormalizeToLineStringGeoJSON(): void
    {
        $line = '{"type": "LineString", "coordinates": [[0, 1], [2, 3]]}';
        $result = $this->polylineMaker->normalizeToLineStringGeoJSON($line);
        $this->assertNotNull($result);
        $decoded = json_decode($result, true);
        $this->assertSame('LineString', $decoded['type']);
        $this->assertArrayHasKey('coordinates', $decoded);
        $this->assertCount(2, $decoded['coordinates']);

        $multi = '{"type": "MultiLineString", "coordinates": [[[0, 1], [2, 3]], [[2, 3], [4, 5]]]}';
        $resultMulti = $this->polylineMaker->normalizeToLineStringGeoJSON($multi);
        $this->assertNotNull($resultMulti);
        $decodedMulti = json_decode($resultMulti, true);
        $this->assertContains($decodedMulti['type'], ['LineString', 'MultiLineString']);
    }

    public function testNormalizeToLineStringGeoJSONReturnsNullForPoint(): void
    {
        $this->assertNull($this->polylineMaker->normalizeToLineStringGeoJSON('{"type": "Point", "coordinates": [0, 1]}'));
    }
}
