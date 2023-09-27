<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class DatexTrafficRegulationView
{
    public function __construct(
        public readonly DatexLocationView $location,
        public readonly array $vehicleConditions,
        public readonly DatexMeasureView $speedLimit,
    ) {
    }
}
