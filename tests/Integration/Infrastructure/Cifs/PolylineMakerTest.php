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
                'polylines' => ['1 0 3 2', '3 2 5 4'],
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
        ];
    }

    /**
     * @dataProvider provideConvertToPolylines
     */
    public function testConvertToPolylines(string $geometry, array $polylines): void
    {
        $this->assertEquals($polylines, $this->polylineMaker->getPolylines($geometry));
    }
}
