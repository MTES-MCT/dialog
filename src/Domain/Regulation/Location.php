<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Application\RoadLine;
use App\Domain\Regulation\Enum\RoadTypeEnum;

class Location
{
    public function __construct(
        private string $uuid,
        private Measure $measure,
        private string $roadType,
        private ?string $administrator,
        private ?string $roadNumber,
        private ?string $cityCode,
        private ?string $cityLabel,
        private ?string $roadName,
        private ?string $fromHouseNumber,
        private ?string $toHouseNumber,
        private ?string $geometry,
        private ?string $roadLineGeometry = null,
        private ?string $roadLineId = null,
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

    public function getRoadLine(): ?RoadLine
    {
        if ($this->roadType === RoadTypeEnum::LANE->value) {
            return $this->roadLineGeometry ? new RoadLine(
                $this->roadLineGeometry,
                $this->roadLineId,
                $this->roadName,
                $this->cityCode,
            ) : null;
        }

        return null;
    }

    public function update(
        string $roadType,
        ?string $administrator,
        ?string $roadNumber,
        ?string $cityCode,
        ?string $cityLabel,
        ?string $roadName,
        ?string $fromHouseNumber,
        ?string $toHouseNumber,
        ?string $geometry,
        ?string $roadLineGeometry,
        ?string $roadLineId,
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
        $this->roadLineGeometry = $roadLineGeometry;
        $this->roadLineId = $roadLineId;
    }
}
