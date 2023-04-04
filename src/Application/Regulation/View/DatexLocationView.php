<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class DatexLocationView
{
    public function __construct(
        public readonly string $address,
        public readonly string $fromHouseNumber,
        public readonly string $fromLongitude,
        public readonly string $fromLatitude,
        public readonly string $toHouseNumber,
        public readonly string $toLongitude,
        public readonly string $toLatitude,
    ) {
    }
}
