<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

class DetailLocationView
{
    public function __construct(
        public readonly string $postalCode,
        public readonly string $city,
        public readonly string $roadName,
        public readonly ?string $fromHouseNumber,
        public readonly ?string $toHouseNumber,
    ) {
    }
}
