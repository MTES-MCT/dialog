<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;

final class SaveLocationCommand implements CommandInterface
{
    public ?string $roadType = null;
    public ?string $administrator = null;
    public ?string $roadNumber = null;
    public ?string $cityCode = null;
    public ?string $cityLabel = null;
    public ?string $roadName = null;
    public ?string $fromHouseNumber = null;
    public ?string $fromRoadName = null;
    public ?Coordinates $fromCoords = null;
    public ?string $toHouseNumber = null;
    public ?string $toRoadName = null;
    public ?Coordinates $toCoords = null;
    public ?string $geometry;
    public ?Measure $measure;
    public ?string $baseLaneGeometry = null;
    public ?string $departmentalRoadGeometry = null;
    private ?bool $isEntireStreetFormValue = null;

    public function __construct(
        public readonly ?Location $location = null,
    ) {
        $this->roadType = $location?->getRoadType();
        $this->administrator = $location?->getAdministrator();
        $this->roadNumber = $location?->getRoadNumber();
        $this->cityCode = $location?->getCityCode();
        $this->cityLabel = $location?->getCityLabel();
        $this->roadName = $location?->getRoadName();
        $this->fromHouseNumber = $location?->getFromHouseNumber();
        $this->toHouseNumber = $location?->getToHouseNumber();
        $this->geometry = $location?->getGeometry();
        $this->isEntireStreetFormValue = $location ? (!$this->fromHouseNumber && !$this->toHouseNumber) : null;
    }

    public function clean(): void
    {
        if ($this->roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value) {
            $this->cityLabel = null;
            $this->cityCode = null;
            $this->roadName = null;
            $this->fromHouseNumber = null;
            $this->toHouseNumber = null;
        }

        if ($this->roadType === RoadTypeEnum::LANE->value || $this->roadType === null) {
            $this->administrator = null;
            $this->roadNumber = null;
            $this->departmentalRoadGeometry = null;
        }

        if ($this->roadType === RoadTypeEnum::LANE->value && $this->isEntireStreetFormValue) {
            $this->fromHouseNumber = null;
            $this->toHouseNumber = null;
        }
    }

    // Used by validation layer

    public function getIsEntireStreet(): bool
    {
        if ($this->isEntireStreetFormValue !== null) {
            return $this->isEntireStreetFormValue;
        }

        return !$this->fromHouseNumber && !$this->toHouseNumber;
    }

    public function setIsEntireStreet(bool $value): void
    {
        $this->isEntireStreetFormValue = $value;
    }
}
