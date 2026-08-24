<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class ZoneView
{
    public function __construct(
        public string $label,
        /** @var WholeCityExceptionView[] */
        public array $exceptions = [],
    ) {
    }
}
