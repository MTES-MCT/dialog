<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Condition\Period\Period;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Location\Location;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Measure
{
    private Collection $locations;
    private Collection $periods;
    private ?VehicleSet $vehicleSet = null;

    public function __construct(
        private string $uuid,
        private RegulationOrder $regulationOrder,
        private string $type,
        private \DateTimeInterface $createdAt,
        private ?int $maxSpeed = null,
    ) {
        $this->periods = new ArrayCollection();
        $this->locations = new ArrayCollection();
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getType(): MeasureTypeEnum
    {
        return MeasureTypeEnum::from($this->type);
    }

    public function getMaxSpeed(): ?int
    {
        return $this->maxSpeed;
    }

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }

    public function getPeriods(): iterable
    {
        return $this->periods;
    }

    public function getLocations(): iterable
    {
        return $this->locations;
    }

    public function getLocation(): ?Location
    {
        return !empty($this->locations) ? $this->locations[0] : null;
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

    public function addLocation(Location $location): void
    {
        if ($this->locations->contains($location)) {
            return;
        }

        $this->locations[] = $location;
    }

    public function removeLocation(Location $location): void
    {
        if (!$this->locations->contains($location)) {
            return;
        }

        $this->locations->removeElement($location);
    }

    public function update(string $type, ?int $maxSpeed): void
    {
        $this->type = $type;
        $this->maxSpeed = $maxSpeed;
    }
}
