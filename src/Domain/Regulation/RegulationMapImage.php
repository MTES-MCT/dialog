<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

final readonly class RegulationMapImage
{
    public function __construct(
        public string $base64Jpeg,
        public array $measureTypes,
    ) {
    }
}
