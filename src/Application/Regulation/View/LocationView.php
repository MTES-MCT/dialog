<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class LocationView
{
    public function __construct(
        public readonly string $cityCode,
        public readonly string $cityLabel,
        public readonly string $roadName,
    ) {
    }
}
