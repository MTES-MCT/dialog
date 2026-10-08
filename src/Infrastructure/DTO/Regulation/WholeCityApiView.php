<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\WholeCityView;

final readonly class WholeCityApiView
{
    public function __construct(
        public ?string $cityCode,
        public ?string $cityLabel,
        /** @var WholeCityExceptionApiView[] */
        public array $exceptions,
    ) {
    }

    public static function fromView(WholeCityView $view): self
    {
        return new self(
            cityCode: $view->cityCode,
            cityLabel: $view->cityLabel,
            exceptions: WholeCityExceptionApiView::fromViews($view->exceptions),
        );
    }
}
