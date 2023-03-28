<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class LocationView
{
    public function __construct(
        public readonly string $city,
        public readonly string $roadName,
    ) {
    }
}
