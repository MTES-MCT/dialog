<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class Location
{
    public function __construct(
        private string $uuid,
        private Measure $measure,
        private string $roadType,
        private ?string $cityCode = null,
        private ?string $cityLabel = null,
        private ?string $roadName = null,
        private ?string $fromHouseNumber = null,
        private ?string $toHouseNumber = null,
        private ?string $administrator = null,
        private ?string $roadNumber = null,
        private ?string $fromPointNumber = null,
        private ?string $fromSide = null,
        private ?int $fromAbscissa = null,
        private ?string $toPointNumber = null,
        private ?string $toSide = null,
        private ?int $toAbscissa = null,
        private ?string $geometry = null,
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

    public function getAdministrator(): ?string
    {
        return $this->administrator;
    }

    public function getRoadNumber(): ?string
    {
        return $this->roadNumber;
    }

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    public function getCityLabel(): ?string
    {
        return $this->cityLabel;
    }

    public function getRoadName(): ?string
    {
        return $this->roadName;
    }

    public function getFromHouseNumber(): ?string
    {
        return $this->fromHouseNumber;
    }

    public function getToHouseNumber(): ?string
    {
        return $this->toHouseNumber;
    }

    public function getGeometry(): ?string
    {
        return $this->geometry;
    }

    public function getFromPointNumber(): ?string
    {
        return $this->fromPointNumber;
    }

    public function getToPointNumber(): ?string
    {
        return $this->toPointNumber;
    }

    public function getFromSide(): ?string
    {
        return $this->fromSide;
    }

    public function getFromAbscissa(): ?int
    {
        return $this->fromAbscissa;
    }

    public function getToAbscissa(): ?int
    {
        return $this->toAbscissa;
    }

    public function getToSide(): ?string
    {
        return $this->toSide;
    }

    public function update(
        string $roadType,
        ?string $cityCode = null,
        ?string $cityLabel = null,
        ?string $roadName = null,
        ?string $fromHouseNumber = null,
        ?string $toHouseNumber = null,
        ?string $administrator = null,
        ?string $roadNumber = null,
        ?string $fromPointNumber = null,
        ?string $fromSide = null,
        ?int $fromAbscissa = null,
        ?string $toPointNumber = null,
        ?string $toSide = null,
        ?int $toAbscissa = null,
        ?string $geometry = null,
    ): void {
        $this->roadType = $roadType;
        $this->cityCode = $cityCode;
        $this->cityLabel = $cityLabel;
        $this->roadName = $roadName;
        $this->fromHouseNumber = $fromHouseNumber;
        $this->toHouseNumber = $toHouseNumber;
        $this->geometry = $geometry;
        $this->administrator = $administrator;
        $this->roadNumber = $roadNumber;
        $this->fromPointNumber = $fromPointNumber;
        $this->fromSide = $fromSide;
        $this->toPointNumber = $toPointNumber;
        $this->fromAbscissa = $fromAbscissa;
        $this->toAbscissa = $toAbscissa;
        $this->toSide = $toSide;
    }
}
