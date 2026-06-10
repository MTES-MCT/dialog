<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\LocationView;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Application\Regulation\View\PeriodView;

final readonly class MeasureApiView
{
    /**
     * @param PeriodApiView[]   $periods
     * @param LocationApiView[] $locations
     */
    public function __construct(
        public string $uuid,
        public string $type,
        public ?int $maxSpeed,
        public ?VehicleSetApiView $vehicleSet,
        public array $periods,
        public array $locations,
    ) {
    }

    public static function fromView(MeasureView $view): self
    {
        $periods = [];

        foreach ($view->periods ?? [] as $period) {
            \assert($period instanceof PeriodView);
            $periods[] = PeriodApiView::fromView($period);
        }

        $locations = [];

        foreach ($view->locations as $location) {
            \assert($location instanceof LocationView);
            $locations[] = LocationApiView::fromView($location);
        }

        return new self(
            uuid: $view->uuid,
            type: $view->type,
            maxSpeed: $view->maxSpeed,
            vehicleSet: $view->vehicleSet ? VehicleSetApiView::fromView($view->vehicleSet) : null,
            periods: $periods,
            locations: $locations,
        );
    }
}
