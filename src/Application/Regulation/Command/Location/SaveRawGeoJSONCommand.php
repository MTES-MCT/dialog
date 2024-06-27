<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Query\Location\GetRawGeoJSONGeometryQuery;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\RawGeoJSON;

final class SaveRawGeoJSONCommand implements RoadCommandInterface
{
    public ?string $roadType = null; // Used by validation
    public ?string $label = null;
    public ?string $geometry = null;
    public ?Location $location = null;

    public function __construct(
        public readonly ?RawGeoJSON $rawGeoJSON = null,
    ) {
        $this->roadType = $rawGeoJSON?->getLocation()?->getRoadType();
        $this->label = $rawGeoJSON?->getLabel();
        $this->geometry = $rawGeoJSON?->getLocation()->getGeometry();
    }

    // Road command interface

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getGeometryQuery(): QueryInterface
    {
        return new GetRawGeoJSONGeometryQuery($this->geometry);
    }

    public function clean(): void
    {
    }
}
