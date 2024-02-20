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
    public ?bool $isEntireStreet = null;
    public ?string $fromHouseNumber = null;
    public ?string $toHouseNumber = null;
    public ?string $geometry;
    public ?Measure $measure;

    public function __construct(
        public readonly ?LocationNew $locationNew = null,
    ) {
        $this->roadType = $locationNew?->getRoadType();
        $this->administrator = $locationNew?->getAdministrator();
        $this->roadNumber = $locationNew?->getRoadNumber();
        $this->cityCode = $locationNew?->getCityCode();
        $this->cityLabel = $locationNew?->getCityLabel();
        $this->roadName = $locationNew?->getRoadName();
        $this->isEntireStreet = $locationNew ? $locationNew->getIsEntireStreet() : true;
        $this->fromHouseNumber = $locationNew?->getFromHouseNumber();
        $this->toHouseNumber = $locationNew?->getToHouseNumber();
        $this->geometry = $locationNew?->getGeometry();
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

        if ($this->roadType === RoadTypeEnum::LANE->value && $this->isEntireStreet) {
            $this->fromHouseNumber = null;
            $this->toHouseNumber = null;
        }
    }
}
