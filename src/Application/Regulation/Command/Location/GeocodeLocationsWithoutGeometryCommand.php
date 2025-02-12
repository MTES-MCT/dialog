<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;

final class GeocodeLocationsWithoutGeometryCommand implements CommandInterface
{
    public function __construct()
    {
    }
}
