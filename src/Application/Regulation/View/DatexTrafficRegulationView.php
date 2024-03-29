<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class DatexTrafficRegulationView
{
    public function __construct(
        public readonly string $type,
        public readonly DatexLocationView $location,
        public readonly array $vehicleConditions,
        public readonly ?int $maxSpeed = null,
    ) {
    }
}
