<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

class WholeCityException
{
    public function __construct(
        private string $uuid,
        private WholeCity $wholeCity,
        private string $roadBanId,
        private string $roadName,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getWholeCity(): WholeCity
    {
        return $this->wholeCity;
    }

    public function getRoadBanId(): string
    {
        return $this->roadBanId;
    }

    public function getRoadName(): string
    {
        return $this->roadName;
    }

    public function update(
        string $roadBanId,
        string $roadName,
    ): void {
        $this->roadBanId = $roadBanId;
        $this->roadName = $roadName;
    }
}
