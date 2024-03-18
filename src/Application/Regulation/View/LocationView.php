<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class LocationView
{
    public function __construct(
        public readonly ?string $cityCode = null,
        public readonly ?string $cityLabel = null,
        public readonly ?string $roadName = null,
        public readonly ?string $roadNumber = null,
        public readonly ?string $administrator = null,
    ) {
    }
}
