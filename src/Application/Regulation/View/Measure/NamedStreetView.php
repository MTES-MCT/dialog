<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class NamedStreetView
{
    public function __construct(
        public ?string $cityLabel,
        public ?string $roadName,
        public ?string $fromHouseNumber,
        public ?string $toHouseNumber,
    ) {
    }
}
