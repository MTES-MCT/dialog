<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\JOP;

use App\Infrastructure\JOP\JOPExtractor;
use PHPUnit\Framework\TestCase;

final class JOPExtractorTest extends TestCase
{
    private $file;
    private $geoJSON;

    protected function setUp(): void
    {
        $this->file = 'tests/tmp/zones.geojson';

        register_shutdown_function(function () {
            if (file_exists($this->file)) {
                unlink($this->file);
            }
        });

        $this->geoJSON = [
            'features' => ['...'],
        ];

        file_put_contents($this->file, json_encode($this->geoJSON));
    }

    public function testExtract(): void
    {
        $extractor = new JOPExtractor($this->file);

        $this->assertEquals(
            $this->geoJSON,
            $extractor->extractGeoJSON(),
        );
    }
}
