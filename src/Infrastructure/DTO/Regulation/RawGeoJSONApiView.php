<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\RawGeoJSONView;

final readonly class RawGeoJSONApiView
{
    public function __construct(
        public string $label,
        /** @var WholeCityExceptionApiView[] */
        public array $exceptions = [],
        public ?string $direction = null,
    ) {
    }

    public static function fromView(RawGeoJSONView $view): self
    {
        return new self(
            label: $view->label,
            exceptions: WholeCityExceptionApiView::fromViews($view->exceptions),
            direction: $view->direction,
        );
    }
}
