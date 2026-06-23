<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\VehicleSetView;

final readonly class VehicleSetApiView
{
    public function __construct(
        public array $restrictedTypes,
        public array $exemptedTypes,
        public array $maxCharacteristics,
    ) {
    }

    public static function fromView(VehicleSetView $view): self
    {
        return new self(
            restrictedTypes: $view->restrictedTypes,
            exemptedTypes: $view->exemptedTypes,
            maxCharacteristics: $view->maxCharacteristics,
        );
    }
}
