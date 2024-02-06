<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Measure;

final class SaveLocationNewCommand implements CommandInterface
{
    public ?string $roadType;
    public ?string $administrator;
    public ?string $roadNumber;
    public ?string $cityCode;
    public ?string $cityLabel;
    public ?string $roadName;
    public ?string $fromHouseNumber;
    public ?string $toHouseNumber;
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
        $this->fromHouseNumber = $locationNew?->getFromHouseNumber();
        $this->toHouseNumber = $locationNew?->getToHouseNumber();
        $this->geometry = $locationNew?->getGeometry();
    }

    public static function fromLocation(Location $location, LocationNew $locationNew = null): self
    {
        $locationNew = new self($locationNew);
        $locationNew->roadType = $location->getRoadType();
        $locationNew->administrator = $location->getAdministrator();
        $locationNew->roadNumber = $location->getRoadNumber();
        $locationNew->cityLabel = $location->getCityLabel();
        $locationNew->cityCode = $location->getCityCode();
        $locationNew->roadName = $location->getRoadName();
        $locationNew->fromHouseNumber = $location->getFromHouseNumber();
        $locationNew->toHouseNumber = $location->getToHouseNumber();
        $locationNew->geometry = $location->getGeometry();

        return $locationNew;
    }
}
