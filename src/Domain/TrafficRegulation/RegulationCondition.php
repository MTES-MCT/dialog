<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation;

class RegulationCondition
{
    public function __construct(
        private string $uuid,
        private bool $negate,
        private TrafficRegulation $trafficRegulation,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function isNegate(): bool
    {
        return $this->negate;
    }

    public function getTrafficRegulation(): TrafficRegulation
    {
        return $this->trafficRegulation;
    }
}
