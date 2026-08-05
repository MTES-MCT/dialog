<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Query\Location\GetZoneGeometryQuery;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\Zone;

final class SaveZoneCommand implements RoadCommandInterface
{
    public ?string $roadType = null; // Utilisé par la validation
    public ?string $label = null;
    // Périmètre dessiné (polygone GeoJSON). La géométrie de la localisation (les tronçons
    // de rues couverts par la zone) est calculée à partir de ce périmètre à l'enregistrement.
    public ?string $geometry = null;
    public ?Location $location = null;

    public function __construct(
        public readonly ?Zone $zone = null,
    ) {
        if ($zone) {
            $this->location = $zone->getLocation();
            $this->roadType = $this->location->getRoadType();
            $this->label = $zone->getLabel();
            $this->geometry = $zone->getGeometry();
        }
    }

    // Road command interface

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getGeometryQuery(): QueryInterface
    {
        return new GetZoneGeometryQuery($this, $this->location);
    }

    public function clean(): void
    {
    }
}
