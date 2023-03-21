<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class Measure
{
    private iterable $regulationConditions = [];

    public function __construct(
        private string $uuid,
        private Location $location,
        private string $type,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getRegulationConditions(): iterable
    {
        return $this->regulationConditions;
    }
}
