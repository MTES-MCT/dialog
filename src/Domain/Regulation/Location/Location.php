<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

use App\Domain\Regulation\Measure;

class Location
{
    public function __construct(
        private string $uuid,
        private Measure $measure,
        private string $roadType,
        private ?string $geometry = null,
        private ?NamedStreet $namedStreet = null,
        private ?NumberedRoad $numberedRoad = null,
    ) {
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

    public function updateGeometry(?string $geometry): void
    {
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
}
