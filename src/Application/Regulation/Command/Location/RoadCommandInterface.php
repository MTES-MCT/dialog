<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Application\QueryInterface;
use App\Domain\Regulation\Location\Location;

interface RoadCommandInterface extends CommandInterface
{
    public function setLocation(Location $location): void;

    public function getGeometryQuery(): QueryInterface;

    public function clean(): void;
}
