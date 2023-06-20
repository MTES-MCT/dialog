<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Condition\VehicleSet;

interface VehicleSetRepositoryInterface
{
    public function add(VehicleSet $vehicleSet): VehicleSet;
}
