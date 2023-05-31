<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Condition\Period\Period;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Measure
{
    private Collection $periods;

    public function __construct(
        private string $uuid,
        private Location $location,
        private string $type,
        private \DateTimeInterface $createdAt,
        private array $restrictedVehicleTypes = [],
        private ?string $otherRestrictedVehicleTypeText = null,
        private array $exemptedVehicleTypes = [],
        private ?string $otherExemptedVehicleTypeText = null,
    ) {
        $this->periods = new ArrayCollection();
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

    public function getPeriods(): iterable
    {
        return $this->periods;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function addPeriod(Period $period): void
    {
        if ($this->periods->contains($period)) {
            return;
        }

        $this->periods[] = $period;
    }

    public function removePeriod(Period $period): void
    {
        if (!$this->periods->contains($period)) {
            return;
        }

        $this->periods->removeElement($period);
    }

    public function getRestrictedVehicleTypes(): array
    {
        return $this->restrictedVehicleTypes;
    }

    public function getOtherRestrictedVehicleTypeText(): string|null
    {
        return $this->otherRestrictedVehicleTypeText;
    }

    public function getExemptedVehicleTypes(): array
    {
        return $this->exemptedVehicleTypes;
    }

    public function getOtherExemptedVehicleTypeText(): string|null
    {
        return $this->otherExemptedVehicleTypeText;
    }

    public function update(
        string $type,
        array $restrictedVehicleTypes = null,
        string $otherRestrictedVehicleTypeText = null,
        array $exemptedVehicleTypes = null,
        string $otherExemptedVehicleTypeText = null,
    ): void {
        $this->type = $type;
        $this->restrictedVehicleTypes = $restrictedVehicleTypes ?: [];
        $this->otherRestrictedVehicleTypeText = $otherRestrictedVehicleTypeText;
        $this->exemptedVehicleTypes = $exemptedVehicleTypes ?: [];
        $this->otherExemptedVehicleTypeText = $otherExemptedVehicleTypeText;
    }
}
