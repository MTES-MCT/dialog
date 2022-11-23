<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation;

use App\Domain\TrafficRegulation\Enum\TrafficRegulationType;

class TrafficRegulation
{
    public function __construct(
        private string $uuid,
        private TrafficRegulationType $type = TrafficRegulationType::NO_ENTRY,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getType(): TrafficRegulationType
    {
        return $this->type;
    }
}
