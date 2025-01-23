<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

use App\Domain\Regulation\Measure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Location
{
    private Collection $storageAreas;

    public function __construct(
        private string $uuid,
        private Measure $measure,
        private string $roadType,
        private ?string $geometry = null,
        private ?NamedStreet $namedStreet = null,
        private ?NumberedRoad $numberedRoad = null,
        private ?RawGeoJSON $rawGeoJSON = null,
    ) {
        $this->storageAreas = new ArrayCollection();
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getMeasure(): Measure
    {
        return $this->measure;
    }

    public function getRoadType(): string
    {
        return $this->roadType;
    }

    public function getGeometry(): ?string
    {
        return $this->geometry;
    }

    public function getNumberedRoad(): ?NumberedRoad
    {
        return $this->numberedRoad;
    }

    public function getNamedStreet(): ?NamedStreet
    {
        return $this->namedStreet;
    }

    public function getRawGeoJSON(): ?RawGeoJSON
    {
        return $this->rawGeoJSON;
    }

    public function getStorageArea(): ?StorageArea
    {
        return $this->storageAreas->first() ?: null;
    }

    public function getCifsStreetLabel(): string
    {
        if ($this->namedStreet) {
            return $this->namedStreet->getRoadName();
        }

        if ($this->numberedRoad) {
            return $this->numberedRoad->getRoadNumber();
        }

        return $this->rawGeoJSON->getLabel();
    }

    public function update(string $roadType, ?string $geometry): void
    {
        $this->roadType = $roadType;
        $this->geometry = $geometry;
    }

    public function setNamedStreet(NamedStreet $namedStreet): void
    {
        $this->namedStreet = $namedStreet;
    }

    public function setNumberedRoad(NumberedRoad $numberedRoad): void
    {
        $this->numberedRoad = $numberedRoad;
    }

    public function setRawGeoJSON(RawGeoJSON $rawGeoJSON): void
    {
        $this->rawGeoJSON = $rawGeoJSON;
    }

    public function setStorageArea(StorageArea $storageArea): void
    {
        if ($this->storageAreas->contains($storageArea)) {
            return;
        }

        if (!$this->storageAreas->isEmpty()) {
            $this->storageAreas->remove(0);
        }

        $this->storageAreas[] = $storageArea;
    }
}
