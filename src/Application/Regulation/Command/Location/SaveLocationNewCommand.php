<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Measure;

final class SaveLocationNewCommand implements CommandInterface
{
    public ?string $roadType = null;
    public ?string $administrator = null;
    public ?string $roadNumber = null;
    public ?string $cityCode = null;
    public ?string $cityLabel = null;
    public ?string $roadName = null;
    public ?string $preComputedRoadGeometry = null;
    public ?string $fromHouseNumber = null;
    public ?string $toHouseNumber = null;
    public ?string $geometry;
    public ?Measure $measure;
    private ?bool $isEntireStreetFormValue = null;

    public function __construct(
        public readonly ?LocationNew $locationNew = null,
    ) {
        $this->roadType = $locationNew?->getRoadType();
        $this->administrator = $locationNew?->getAdministrator();
        $this->roadNumber = $locationNew?->getRoadNumber();
        $this->cityCode = $locationNew?->getCityCode();
        $this->cityLabel = $locationNew?->getCityLabel();
        $this->roadName = $locationNew?->getRoadName();
        $this->fromHouseNumber = $locationNew?->getFromHouseNumber();
        $this->toHouseNumber = $locationNew?->getToHouseNumber();
        $this->geometry = $locationNew?->getGeometry();
        $this->isEntireStreetFormValue = $locationNew ? (!$this->fromHouseNumber && !$this->toHouseNumber) : null;
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
