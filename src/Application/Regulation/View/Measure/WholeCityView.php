<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class WholeCityView
{
    public function __construct(
        public ?string $cityCode,
        public ?string $cityLabel,
    ) {
    }
}
