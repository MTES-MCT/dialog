<?php

declare(strict_types=1);

namespace App\Application\Condition\Query\VehicleCharacteristics;

use App\Domain\Condition\Repository\VehicleCharacteristicsRepositoryInterface;
use App\Domain\Condition\VehicleCharacteristics;

final class GetVehicleCharacteristicsByRegulationConditionQueryHandler
{
    public function __construct(
        private VehicleCharacteristicsRepositoryInterface $vehicleCharacteristicsRepository,
    ) {
    }

    public function __invoke(GetVehicleCharacteristicsByRegulationConditionQuery $query): ?VehicleCharacteristics
    {
        return $this->vehicleCharacteristicsRepository
            ->findOneByRegulationConditionUuid($query->regulationConditionUuid);
    }
}
