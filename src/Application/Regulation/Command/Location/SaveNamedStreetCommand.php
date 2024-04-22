<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Measure;

final class SaveNamedStreetCommand implements CommandInterface
{
    public ?string $roadType = null;
    public ?string $cityCode = null;
    public ?string $cityLabel = null;
    public ?string $roadName = null;
    public ?string $fromHouseNumber = null;
    public ?string $fromRoadName = null;
    public ?Coordinates $fromCoords = null;
    public ?string $toHouseNumber = null;
    public ?string $toRoadName = null;
    public ?Coordinates $toCoords = null;
    private ?bool $isEntireStreetFormValue = null;
    public ?string $geometry = null;
    public ?Measure $measure;

    public function __construct(
        public readonly ?NamedStreet $namedStreet = null,
    ) {
        $this->cityLabel = $namedStreet?->getCityLabel();
        $this->cityCode = $namedStreet?->getCityCode();
        $this->roadName = $namedStreet?->getRoadName();
        $this->fromHouseNumber = $namedStreet?->getFromHouseNumber();
        $this->toHouseNumber = $namedStreet?->getToHouseNumber();
        $this->isEntireStreetFormValue = $namedStreet ? (!$this->fromHouseNumber && !$this->toHouseNumber) : null;
    }

    public function clean(): void
    {
        if ($this->isEntireStreetFormValue) {
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
