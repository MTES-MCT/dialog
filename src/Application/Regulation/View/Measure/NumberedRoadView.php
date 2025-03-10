<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

use App\Domain\Regulation\Location\NumberedRoad;

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

    public function hasPointNumbers(): bool
    {
        return !NumberedRoad::isPointNumberEmpty($this->fromPointNumber) && !NumberedRoad::isPointNumberEmpty($this->toPointNumber);
    }
}
