<?php

declare(strict_types=1);

namespace App\Infrastructure\JOP;

final class JOPExtractor
{
    public function __construct(
        private readonly string $jopGeoJSONFile,
    ) {
    }

    public function extractGeoJSON(): array
    {
        return json_decode(file_get_contents($this->jopGeoJSONFile), associative: true);
    }
}
