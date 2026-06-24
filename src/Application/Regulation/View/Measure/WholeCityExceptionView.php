<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class WholeCityExceptionView
{
    public function __construct(
        public string $roadType,
        public string $label,
        public ?string $fromHouseNumber = null,
        public ?string $fromRoadName = null,
        public ?string $toHouseNumber = null,
        public ?string $toRoadName = null,
    ) {
    }
}
