<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class NumberedRoadView
{
    public function __construct(
        public ?string $administrator,
        public ?string $roadNumber,
        public ?string $fromPointNumber,
        public ?int $fromAbscissa,
        public ?string $fromSide,
        public ?string $toPointNumber,
        public ?int $toAbscissa,
        public ?string $toSide,
    ) {
    }
}
