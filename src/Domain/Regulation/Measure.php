<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Condition\Period\Period;
use App\Domain\Condition\VehicleSet;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Measure
{
    private Collection $periods;
    private ?VehicleSet $vehicleSet = null;

    public function __construct(
        private string $uuid,
        private Location $location,
        private string $type,
        private \DateTimeInterface $createdAt,
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

    public function getVehicleSet(): ?VehicleSet
    {
        return $this->vehicleSet;
    }

    public function setVehicleSet(?VehicleSet $vehicleSet): void
    {
        $this->vehicleSet = $vehicleSet;
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

    public function update(string $type): void
    {
        $this->type = $type;
    }
}
