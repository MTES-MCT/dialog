<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class Location
{
    public function __construct(
        private string $uuid,
        private Measure $measure,
        private string $roadType,
        private ?string $administrator = null,
        private ?string $roadNumber = null,
        private ?string $cityCode = null,
        private ?string $cityLabel = null,
        private ?string $roadName = null,
        private ?string $fromHouseNumber = null,
        private ?string $toHouseNumber = null,
        private ?string $geometry = null,
        /**
         * [
         *     'pointA' => [
         *          'pointNumber' => 14,
         *          'abscissa' => 650,
         *      ],
         *     'pointB' => [
         *          'pointNumber' => 16,
         *          'abscissa' => 250,
         *      ],
         *      'direction' => 'U', // U|G|D
         * ]
         */
        private ?array $referencePoints = null,
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

    public function getDirection(): ?string
    {
        if (!$this->referencePoints) {
            return null;
        }

        return $this->referencePoints['direction'] ?? null;
    }

    public function getPointA(): ?array
    {
        if (!$this->referencePoints) {
            return null;
        }

        return $this->referencePoints['pointA'] ?? null;
    }

    public function getPointB(): ?array
    {
        if (!$this->referencePoints) {
            return null;
        }

        return $this->referencePoints['pointB'] ?? null;
    }

    public function update(
        string $roadType,
        string $administrator = null,
        string $roadNumber = null,
        string $cityCode = null,
        string $cityLabel = null,
        string $roadName = null,
        string $fromHouseNumber = null,
        string $toHouseNumber = null,
        string $geometry = null,
        array $referencePoints = null,
    ): void {
        $this->roadType = $roadType;
        $this->administrator = $administrator;
        $this->roadNumber = $roadNumber;
        $this->cityCode = $cityCode;
        $this->cityLabel = $cityLabel;
        $this->roadName = $roadName;
        $this->fromHouseNumber = $fromHouseNumber;
        $this->toHouseNumber = $toHouseNumber;
        $this->geometry = $geometry;
        $this->referencePoints = $referencePoints;
    }
}
