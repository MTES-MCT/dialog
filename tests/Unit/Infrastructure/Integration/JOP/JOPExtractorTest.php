<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Integration\JOP;

use App\Infrastructure\Integration\JOP\JOPExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

final class JOPExtractorTest extends TestCase
{
    public function testExtract(): void
    {
        // Prepare a test file in a test folder

        $file = 'tests/tmp/zones.geojson';

        register_shutdown_function(function () use ($file) {
            if (file_exists($file)) {
                unlink($file);
            }
        });

        file_put_contents($file, json_encode([
            'type' => 'FeatureCollection',
            'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
            'features' => [
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        // Will get CRS from FeatureCollection
                        'coordinates' => '...',
                    ],
                ],
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        // Will use this CRS
                        'crs' => ['type' => 'name', 'properties' => ['name' => 'crs:TEST']],
                        'coordinates' => '...',
                    ],
                ],
                [
                    // Empty geometry
                    'type' => 'Feature',
                    'geometry' => null,
                ],
            ],
        ]));

        // Extract it

        $kernel = $this->createMock(KernelInterface::class);

        $kernel
            ->expects(self::never()) // It muse use our test folder
            ->method('getProjectDir');

        $extractor = new JOPExtractor($kernel);

        $this->assertEquals(
            [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Polygon',
                            'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
                            'coordinates' => '...',
                        ],
                    ],
                    [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Polygon',
                            'crs' => ['type' => 'name', 'properties' => ['name' => 'crs:TEST']],
                            'coordinates' => '...',
                        ],
                    ],
                ],
            ],
            $extractor->extractGeoJSON('tests/tmp'),
        );
    }
}
