<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class Measure
{
    private iterable $conditions = [];

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

    public function getConditions(): iterable
    {
        return $this->conditions;
    }

    public function update(string $type): void
    {
        $this->type = $type;
    }
}
