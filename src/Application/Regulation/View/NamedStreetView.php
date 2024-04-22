<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class NamedStreetView implements LocationViewInterface
{
    public function __construct(
        public ?string $cityCode = null,
        public ?string $cityLabel = null,
        public ?string $roadName = null,
    ) {
    }
}
