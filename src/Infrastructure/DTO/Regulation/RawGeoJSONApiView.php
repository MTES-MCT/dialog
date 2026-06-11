<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\RawGeoJSONView;

final readonly class RawGeoJSONApiView
{
    public function __construct(
        public string $label,
    ) {
    }

    public static function fromView(RawGeoJSONView $view): self
    {
        return new self(label: $view->label);
    }
}
