<?php

declare(strict_types=1);

namespace App\Domain\Condition\Location;

class RoadInformation
{
    public function __construct(
        private string $uuid,
        private SupplementaryPositionalDescription $supplementaryPositionalDescription,
        private ?string $roadDestination,
        private ?string $roadNumber,
        private ?string $roadName,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRoadDestination(): ?string
    {
        return $this->roadDestination;
    }

    public function getRoadNumber(): ?string
    {
        return $this->roadNumber;
    }

    public function getRoadName(): ?string
    {
        return $this->roadName;
    }

    public function getSupplementaryPositionalDescription(): SupplementaryPositionalDescription
    {
        return $this->supplementaryPositionalDescription;
    }
}
