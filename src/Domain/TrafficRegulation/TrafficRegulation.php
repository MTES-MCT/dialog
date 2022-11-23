<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation;

use App\Domain\TrafficRegulation\Enum\TrafficRegulationEnum;

class TrafficRegulation
{
    public function __construct(
        private string $uuid,
        private TrafficRegulationEnum $type = TrafficRegulationEnum::NO_ENTRY,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getType(): TrafficRegulationEnum
    {
        return $this->type;
    }
}
