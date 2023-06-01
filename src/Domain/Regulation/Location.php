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
        private string $address,
        private ?string $fromHouseNumber,
        private ?string $fromPoint,
        private ?string $toHouseNumber,
        private ?string $toPoint,
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

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getFromHouseNumber(): ?string
    {
        return $this->fromHouseNumber;
    }

    public function getFromPoint(): ?string
    {
        return $this->fromPoint;
    }

    public function getToHouseNumber(): ?string
    {
        return $this->toHouseNumber;
    }

    public function getToPoint(): ?string
    {
        return $this->toPoint;
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
        string $address,
        ?string $fromHouseNumber,
        ?string $fromPoint,
        ?string $toHouseNumber,
        ?string $toPoint,
    ): void {
        $this->address = $address;
        $this->fromHouseNumber = $fromHouseNumber;
        $this->fromPoint = $fromPoint;
        $this->toHouseNumber = $toHouseNumber;
        $this->toPoint = $toPoint;
    }
}
