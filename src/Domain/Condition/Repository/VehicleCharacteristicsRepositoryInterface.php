<?php

declare(strict_types=1);

namespace App\Domain\Condition\Repository;

use App\Domain\Condition\VehicleCharacteristics;

interface VehicleCharacteristicsRepositoryInterface
{
    public function save(VehicleCharacteristics $vehicleCharacteristics): VehicleCharacteristics;

    public function findOneByRegulationConditionUuid(string $uuid): ?VehicleCharacteristics;
}
