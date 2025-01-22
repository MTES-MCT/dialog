<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\JOP;

use Symfony\Component\HttpKernel\KernelInterface;

final class JOPExtractor
{
    private const CRS_MAP = [
        'urn:ogc:def:crs:OGC:1.3:CRS84' => 'EPSG:4326',
    ];

    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    private function mapCrs(array $crs): array
    {
        if (\array_key_exists($crs['properties']['name'], self::CRS_MAP)) {
            $crs = ['type' => 'name', 'properties' => ['name' => self::CRS_MAP[$crs['properties']['name']]]];
        }

        return $crs;
    }

    public function extractGeoJSON(?string $dir = null): array
    {
        if (!$dir) {
            $dir = $this->kernel->getProjectDir() . '/data/jop/files';
        }

        $pattern = \sprintf('%s/*.geojson', $dir);

        $features = [];

        foreach (glob($pattern) as $filename) {
            $featureCollection = json_decode(file_get_contents($filename), associative: true);

            foreach ($featureCollection['features'] as $feature) {
                if (!$feature['geometry']) {
                    // Sometimes geometry is null, skip
                    continue;
                }

                // CRS (Coordinate Reference System) may be present at FeatureCollection or Feature level.
                // FeatureCollection CRS may differ among files.
                // We include it in the 'geometry' of every Feature for PostGIS to consume.
                $crs = $feature['geometry']['crs'] ?? $featureCollection['crs'];
                $feature['geometry']['crs'] = $this->mapCrs($crs);

                $features[] = $feature;
            }
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }
}
