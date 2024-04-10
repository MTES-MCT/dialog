<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class DatexTrafficRegulationView
{
    public function __construct(
        public string $type,
        public array $locationConditions,
        public array $vehicleConditions,
        public array $validityConditions,
    ) {
    }
}
