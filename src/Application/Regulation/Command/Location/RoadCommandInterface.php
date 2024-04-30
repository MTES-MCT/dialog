<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Domain\Regulation\Location\Location;

interface RoadCommandInterface
{
    public function setLocation(Location $location): void;

    public function getGeometryQuery(): QueryInterface;
}
