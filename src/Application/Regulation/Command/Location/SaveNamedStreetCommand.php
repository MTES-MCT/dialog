<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Query\Location\GetNamedStreetGeometryQuery;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;

final class SaveNamedStreetCommand implements RoadCommandInterface
{
    public ?string $roadType = null; // Used by validation
    public ?string $cityCode = null;
    public ?string $cityLabel = null;
    public ?string $roadBanId = null;
    public ?string $roadName = null;
    public ?string $fromPointType = null;
    public ?string $fromHouseNumber = null;
    public ?string $fromRoadName = null;
    public ?Coordinates $fromCoords = null;
    public ?string $toPointType = null;
    public ?string $toHouseNumber = null;
    public ?string $toRoadName = null;
    public ?Coordinates $toCoords = null;
    private ?bool $isEntireStreetFormValue = null;
    public ?string $geometry = null;
    public ?Location $location = null;
    public string $direction = DirectionEnum::BOTH->value;

    public function __construct(
        public readonly ?NamedStreet $namedStreet = null,
    ) {
        $this->cityLabel = $namedStreet?->getCityLabel();
        $this->cityCode = $namedStreet?->getCityCode();
        $this->roadBanId = $namedStreet?->getRoadBanId();
        $this->roadName = $namedStreet?->getRoadName();
        $this->fromPointType = $namedStreet?->getFromPointType();
        $this->fromHouseNumber = $namedStreet?->getFromHouseNumber();
        $this->fromRoadName = $namedStreet?->getFromRoadName();
        $this->toPointType = $namedStreet?->getToPointType();
        $this->toHouseNumber = $namedStreet?->getToHouseNumber();
        $this->toRoadName = $namedStreet?->getToRoadName();
        $this->isEntireStreetFormValue = $namedStreet ? $this->computeIsEntireStreetFormValue() : null;
        $this->roadType = $namedStreet?->getLocation()?->getRoadType();
        $this->direction = $namedStreet?->getDirection() ?? DirectionEnum::BOTH->value;
    }

    public function clean(): void
    {
        if ($this->isEntireStreetFormValue) {
            $this->fromHouseNumber = null;
            $this->toHouseNumber = null;
        }
    }

    private function computeIsEntireStreetFormValue(): bool
    {
        return !$this->fromHouseNumber && !$this->fromRoadName && !$this->toHouseNumber && !$this->toRoadName;
    }

    // Used by validation layer

    public function getIsEntireStreet(): bool
    {
        if ($this->isEntireStreetFormValue !== null) {
            return $this->isEntireStreetFormValue;
        }

        return $this->computeIsEntireStreetFormValue();
    }

    public function setIsEntireStreet(bool $value): void
    {
        $this->isEntireStreetFormValue = $value;
    }

    // Road command interface

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getGeometryQuery(): QueryInterface
    {
        return new GetNamedStreetGeometryQuery($this, $this->location, $this->geometry);
    }
}
