<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\ZoneView;

final readonly class ZoneApiView
{
    public function __construct(
        public string $label,
    ) {
    }

    public static function fromView(ZoneView $view): self
    {
        return new self(label: $view->label);
    }
}
