<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class DatexTrafficRegulationView
{
    public function __construct(
        public readonly string $type,
        public readonly array $locationConditions,
        public readonly array $vehicleConditions,
        public readonly array $validityConditions,
        public readonly ?int $maxSpeed = null,
    ) {
    }
}
