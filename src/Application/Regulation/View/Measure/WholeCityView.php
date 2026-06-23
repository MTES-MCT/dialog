<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class WholeCityView
{
    /**
     * @param string[] $exceptions Road names excluded from the restriction
     */
    public function __construct(
        public ?string $cityCode,
        public ?string $cityLabel,
        public array $exceptions = [],
    ) {
    }
}
