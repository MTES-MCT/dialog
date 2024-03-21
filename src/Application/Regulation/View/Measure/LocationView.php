<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class LocationView
{
    public function __construct(
        public ?string $roadType,
        public ?string $cityLabel,
        public ?string $roadName,
        public ?string $fromHouseNumber,
        public ?string $toHouseNumber,
        public ?string $administrator,
        public ?string $roadNumber,
        public ?string $fromPointNumber,
        public ?int $fromAbscissa,
        public ?string $toPointNumber,
        public ?int $toAbscissa,
    ) {
    }
}
