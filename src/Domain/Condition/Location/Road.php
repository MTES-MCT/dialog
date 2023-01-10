<?php

declare(strict_types=1);

namespace App\Domain\Condition\Location;

class Road
{
    public function __construct(
        private string $name,
        private int $fromHouseNumber,
        private int $toHouseNumber,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFromHouseNumber(): int
    {
        return $this->fromHouseNumber;
    }

    public function getToHouseNumber(): int
    {
        return $this->toHouseNumber;
    }
}
