<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\NumberedRoadView;

final readonly class NumberedRoadApiView
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

    public static function fromView(NumberedRoadView $view): self
    {
        return new self(
            administrator: $view->administrator,
            roadNumber: $view->roadNumber,
            fromPointNumber: $view->fromPointNumber,
            fromAbscissa: $view->fromAbscissa,
            fromSide: $view->fromSide,
            toPointNumber: $view->toPointNumber,
            toAbscissa: $view->toAbscissa,
            toSide: $view->toSide,
        );
    }
}
