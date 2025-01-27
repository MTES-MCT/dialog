<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

class StorageArea
{
    public function __construct(
        private string $uuid,
        private Location $location,
        private string $sourceId,
        private string $description,
        private string $administrator,
        private string $roadNumber,
        private string $fromPointNumber,
        private string $fromSide,
        private int $fromAbscissa,
        private string $toPointNumber,
        private string $toSide,
        private int $toAbscissa,
        private string $geometry,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAdministrator(): string
    {
        return $this->administrator;
    }

    public function getRoadNumber(): string
    {
        return $this->roadNumber;
    }

    public function getFromPointNumber(): string
    {
        return $this->fromPointNumber;
    }

    public function getToPointNumber(): string
    {
        return $this->toPointNumber;
    }

    public function getFromSide(): string
    {
        return $this->fromSide;
    }

    public function getFromAbscissa(): int
    {
        return $this->fromAbscissa;
    }

    public function getToSide(): string
    {
        return $this->toSide;
    }

    public function getToAbscissa(): int
    {
        return $this->toAbscissa;
    }

    public function getGeometry(): string
    {
        return $this->geometry;
    }
}
