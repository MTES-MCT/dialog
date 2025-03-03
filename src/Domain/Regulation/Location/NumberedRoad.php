<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

class NumberedRoad
{
    public function __construct(
        private string $uuid,
        private Location $location,
        private string $direction,
        private ?string $administrator = null,
        private ?string $roadNumber = null,
        private ?string $fromDepartmentCode = null,
        private ?string $fromPointNumber = null,
        private ?string $fromSide = null,
        private ?int $fromAbscissa = null,
        private ?string $toDepartmentCode = null,
        private ?string $toPointNumber = null,
        private ?string $toSide = null,
        private ?int $toAbscissa = null,
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

    public function getAdministrator(): ?string
    {
        return $this->administrator;
    }

    public function getRoadNumber(): ?string
    {
        return $this->roadNumber;
    }

    public function getFromPointNumber(): ?string
    {
        return $this->fromPointNumber;
    }

    public function getToPointNumber(): ?string
    {
        return $this->toPointNumber;
    }

    public function getFromDepartmentCode(): ?string
    {
        return $this->fromDepartmentCode;
    }

    public function getToDepartmentCode(): ?string
    {
        return $this->toDepartmentCode;
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

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function update(
        string $direction,
        ?string $administrator = null,
        ?string $roadNumber = null,
        ?string $fromDepartmentCode = null,
        ?string $fromPointNumber = null,
        ?string $fromSide = null,
        ?int $fromAbscissa = null,
        ?string $toDepartmentCode = null,
        ?string $toPointNumber = null,
        ?string $toSide = null,
        ?int $toAbscissa = null,
    ): void {
        $this->administrator = $administrator;
        $this->roadNumber = $roadNumber;
        $this->fromDepartmentCode = $fromDepartmentCode;
        $this->fromPointNumber = $fromPointNumber;
        $this->fromSide = $fromSide;
        $this->fromAbscissa = $fromAbscissa;
        $this->toDepartmentCode = $toDepartmentCode;
        $this->toPointNumber = $toPointNumber;
        $this->toSide = $toSide;
        $this->toAbscissa = $toAbscissa;
        $this->direction = $direction;
    }
}
