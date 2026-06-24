<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Measure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Location
{
    /** @var Collection<int, WholeCityException> */
    private Collection $exceptions;

    public function __construct(
        private string $uuid,
        private Measure $measure,
        private string $roadType,
        private ?string $geometry = null,
        private ?NamedStreet $namedStreet = null,
        private ?NumberedRoad $numberedRoad = null,
        private ?RawGeoJSON $rawGeoJSON = null,
        private ?StorageArea $storageArea = null,
        // Utilisé uniquement par le type « Ville entière » (wholeCity), qui n'a pas de
        // sous-entité dédiée : la géométrie de la ville vit directement sur la localisation.
        private ?string $cityCode = null,
        private ?string $cityLabel = null,
    ) {
        $this->exceptions = new ArrayCollection();
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
        return $this->storageArea;
    }

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    public function getCityLabel(): ?string
    {
        return $this->cityLabel;
    }

    /**
     * Voies/tracés exclus d'une restriction « Ville entière ».
     *
     * @return WholeCityException[]
     */
    public function getExceptions(): array
    {
        return array_values($this->exceptions->toArray());
    }

    public function addException(WholeCityException $exception): void
    {
        if (!$this->exceptions->contains($exception)) {
            $this->exceptions->add($exception);
        }
    }

    public function removeException(WholeCityException $exception): void
    {
        $this->exceptions->removeElement($exception);
    }

    public function getCifsStreetLabel(): string
    {
        if ($this->namedStreet) {
            return $this->namedStreet->getRoadName();
        }

        if ($this->numberedRoad) {
            return $this->numberedRoad->getRoadNumber();
        }

        if ($this->cityLabel) {
            return $this->cityLabel;
        }

        return $this->rawGeoJSON->getLabel();
    }

    public function update(string $roadType, ?string $geometry): void
    {
        $this->roadType = $roadType;
        $this->geometry = $geometry;

        // The city fields only make sense for the "ville entière" road type.
        if ($roadType !== RoadTypeEnum::WHOLE_CITY->value) {
            $this->cityCode = null;
            $this->cityLabel = null;
        }
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

    public function setStorageArea(?StorageArea $storageArea): void
    {
        $this->storageArea = $storageArea;
    }

    public function setWholeCity(?string $cityCode, ?string $cityLabel): void
    {
        $this->cityCode = $cityCode;
        $this->cityLabel = $cityLabel;
    }
}
