<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\NamedStreetView;

final readonly class NamedStreetApiView
{
    public function __construct(
        public ?string $cityLabel,
        public ?string $roadName,
        public ?string $fromHouseNumber,
        public ?string $fromRoadName,
        public ?string $toHouseNumber,
        public ?string $toRoadName,
    ) {
    }

    public static function fromView(NamedStreetView $view): self
    {
        return new self(
            cityLabel: $view->cityLabel,
            roadName: $view->roadName,
            fromHouseNumber: $view->fromHouseNumber,
            fromRoadName: $view->fromRoadName,
            toHouseNumber: $view->toHouseNumber,
            toRoadName: $view->toRoadName,
        );
    }
}
