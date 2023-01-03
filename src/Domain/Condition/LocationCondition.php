<?php

declare(strict_types=1);

namespace App\Domain\Condition;

class LocationCondition
{
    public function __construct(
        private string $uuid,
        private RegulationCondition $regulationCondition,
        private string $geometry,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getGeometry(): string
    {
        return $this->geometry;
    }

    public function getRegulationCondition(): RegulationCondition
    {
        return $this->regulationCondition;
    }
}
