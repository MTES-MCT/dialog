<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class WholeCityView
{
    /**
     * @param WholeCityExceptionView[] $exceptions Voies/tracés exclus de la restriction
     */
    public function __construct(
        public ?string $cityCode,
        public ?string $cityLabel,
        public array $exceptions = [],
    ) {
    }
}
