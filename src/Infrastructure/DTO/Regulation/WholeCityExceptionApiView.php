<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\WholeCityExceptionView;

/**
 * Exception (« Sauf... ») d'une localisation « Ville entière », « Tracé de zone » ou
 * « Tracé libre » : emprise exclue de la restriction.
 */
final readonly class WholeCityExceptionApiView
{
    public function __construct(
        public string $roadType,
        public string $label,
        public ?string $fromHouseNumber,
        public ?string $fromRoadName,
        public ?string $toHouseNumber,
        public ?string $toRoadName,
        public ?NumberedRoadApiView $numberedRoad,
    ) {
    }

    public static function fromView(WholeCityExceptionView $view): self
    {
        return new self(
            roadType: $view->roadType,
            label: $view->label,
            fromHouseNumber: $view->fromHouseNumber,
            fromRoadName: $view->fromRoadName,
            toHouseNumber: $view->toHouseNumber,
            toRoadName: $view->toRoadName,
            numberedRoad: $view->numberedRoad ? NumberedRoadApiView::fromView($view->numberedRoad) : null,
        );
    }

    /**
     * @param WholeCityExceptionView[] $views
     *
     * @return self[]
     */
    public static function fromViews(array $views): array
    {
        return array_map(self::fromView(...), $views);
    }
}
