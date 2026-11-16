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

    public function testGetPolylinesMergesConnectedLines(): void
    {
        $geometry = '{"type": "MultiLineString", "coordinates": [[[0, 1], [2, 3]], [[2, 3], [4, 5]]]}';
        $this->assertSame(['1 0 3 2 5 4'], $this->polylineMaker->getPolylines($geometry));
    }

    public function testGetPolylinesLineString(): void
    {
        $geometry = '{"type": "LineString", "coordinates": [[0, 1], [2, 3]]}';
        $this->assertSame(['1 0 3 2'], $this->polylineMaker->getPolylines($geometry));
    }

    public function testGetPolylinesKeepsDisjointLinesSeparate(): void
    {
        // Deux emprises séparées de plusieurs kilomètres : elles ne doivent pas être reliées par un trait.
        $geometry = '{"type": "MultiLineString", "coordinates": [
            [[-3.014686584, 48.254442215], [-3.013954845, 48.25302725], [-3.010991824, 48.24834206]],
            [[-2.963690776, 48.278001167], [-2.964337836, 48.276629108], [-2.966538894, 48.270709216]]
        ]}';

        $this->assertSame(
            [
                '48.254442215 -3.014686584 48.25302725 -3.013954845 48.24834206 -3.010991824',
                '48.278001167 -2.963690776 48.276629108 -2.964337836 48.270709216 -2.966538894',
            ],
            $this->polylineMaker->getPolylines($geometry),
        );
    }

    public function testGetPolylinesMergesConnectedLinesAndKeepsDisjointOnesSeparate(): void
    {
        $geometry = '{"type": "MultiLineString", "coordinates": [
            [[0, 1], [2, 3]],
            [[10, 11], [12, 13]],
            [[2, 3], [4, 5]]
        ]}';

        $this->assertSame(
            ['1 0 3 2 5 4', '11 10 13 12'],
            $this->polylineMaker->getPolylines($geometry),
        );
    }

    public function testGetPolylinesDeduplicatesIdenticalLines(): void
    {
        $geometry = '{"type": "MultiLineString", "coordinates": [[[0, 1], [2, 3]], [[0, 1], [2, 3]]]}';
        $this->assertSame(['1 0 3 2'], $this->polylineMaker->getPolylines($geometry));
    }

    public function testGetPolylinesEmpty(): void
    {
        $this->assertSame([], $this->polylineMaker->getPolylines('{"type": "Point", "coordinates": [0, 1]}'));
        $this->assertSame([], $this->polylineMaker->getPolylines('{"type": "LineString", "coordinates": []}'));
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
