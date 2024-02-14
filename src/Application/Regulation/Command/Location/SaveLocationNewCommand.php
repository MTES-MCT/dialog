<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
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
        $this->fromHouseNumber = $locationNew?->getFromHouseNumber();
        $this->toHouseNumber = $locationNew?->getToHouseNumber();
        $this->geometry = $locationNew?->getGeometry();
    }
}
