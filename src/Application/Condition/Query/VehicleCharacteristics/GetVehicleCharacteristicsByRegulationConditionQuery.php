<?php

declare(strict_types=1);

namespace App\Application\Condition\Query\VehicleCharacteristics;

use App\Application\QueryInterface;

final class GetVehicleCharacteristicsByRegulationConditionQuery implements QueryInterface
{
    public function __construct(
        public readonly string $regulationConditionUuid,
    ) {
    }
}
