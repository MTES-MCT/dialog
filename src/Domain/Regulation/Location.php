<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Location
{
    private Collection $measures;

    public function __construct(
        private string $uuid,
        private RegulationOrder $regulationOrder,
        private string $roadType,
        private ?string $cityLabel,
        private ?string $cityCode,
        private ?string $administrator,
        private ?string $roadNumber,
        private ?string $roadName,
        private ?string $fromHouseNumber,
        private ?string $toHouseNumber,
        private ?string $geometry,
        // Deprecated
        private ?string $fromPoint = null,
        private ?string $toPoint = null,
        private ?string $address = null,
    ) {
        $this->measures = new ArrayCollection();
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }

    public function getRoadType(): string
    {
        return $this->roadType;
    }

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    public function getCityLabel(): ?string
    {
        return $this->cityLabel;
    }

    public function getAdministrator(): ?string
    {
        return $this->administrator;
    }

    public function getRoadNumber(): ?string
    {
        return $this->roadNumber;
    }

    public function getRoadName(): ?string
    {
        return $this->roadName;
    }

    public function getFromHouseNumber(): ?string
    {
        return $this->fromHouseNumber;
    }

    public function getGeometry(): ?string
    {
        return $this->geometry;
    }

    public function getToHouseNumber(): ?string
    {
        return $this->toHouseNumber;
    }

    public function getMeasures(): iterable
    {
        return $this->measures;
    }

    public function addMeasure(Measure $measure): void
    {
        if ($this->measures->contains($measure)) {
            return;
        }

        $this->measures->add($measure);
    }

    public function removeMeasure(Measure $measure): void
    {
        if (!$this->measures->contains($measure)) {
            return;
        }

        $this->measures->removeElement($measure);
    }

    public function update(
        string $roadType,
        string $cityCode,
        string $cityLabel,
        ?string $administrator,
        ?string $roadNumber,
        ?string $roadName,
        ?string $fromHouseNumber,
        ?string $toHouseNumber,
        ?string $geometry,
    ): void {
        $this->roadType = $roadType;
        $this->cityCode = $cityCode;
        $this->cityLabel = $cityLabel;
        $this->administrator = $administrator;
        $this->roadNumber = $roadNumber;
        $this->roadName = $roadName;
        $this->fromHouseNumber = $fromHouseNumber;
        $this->toHouseNumber = $toHouseNumber;
        $this->geometry = $geometry;
    }

    // Deprecated

    public function getFromPoint(): ?string
    {
        return $this->fromPoint;
    }

    public function getToPoint(): ?string
    {
        return $this->toPoint;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }
}
