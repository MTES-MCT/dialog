<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\VehicleTypeEnum;

final class DatexTrafficRegulationViewBuilder
{
    private array $currentMeasureRow;
    private ?string $type = null;
    private array $locations = [];
    private array $vehicleConditions = [];
    private array $validityConditions = [];
    private ?int $maxSpeed = null;

    public function init(array $row): void
    {
        $this->currentMeasureRow = $row;
        $this->type = null;
        $this->locations = [];
        $this->vehicleConditions = $this->readVehicleConditions($row);
        $this->validityConditions = [];
        $this->maxSpeed = null;
    }

    private function readVehicleConditions(array $row): array
    {
        $vehicleConditions = [];

        foreach ($row['restrictedVehicleTypes'] ?: [] as $restrictedVehicleType) {
            if (VehicleTypeEnum::CRITAIR->value === $restrictedVehicleType) {
                continue;
            }

            if (VehicleTypeEnum::DIMENSIONS->value === $restrictedVehicleType) {
                $vehicleConditions[] = new DatexVehicleConditionView(
                    vehicleType: $restrictedVehicleType,
                    maxWidth: $row['maxWidth'],
                    maxLength: $row['maxLength'],
                    maxHeight: $row['maxHeight'],
                );
            } elseif (VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value === $restrictedVehicleType) {
                $vehicleConditions[] = new DatexVehicleConditionView(
                    vehicleType: $restrictedVehicleType,
                    maxWeight: $row['heavyweightMaxWeight'],
                );
            } else {
                $vehicleConditions[] = new DatexVehicleConditionView(
                    vehicleType: $restrictedVehicleType,
                );
            }
        }

        foreach ($row['restrictedCritairTypes'] ?: [] as $restrictedCritairTypes) {
            $vehicleConditions[] = new DatexVehicleConditionView($restrictedCritairTypes);
        }

        foreach ($row['exemptedVehicleTypes'] ?: [] as $exemptedVehicleType) {
            $vehicleConditions[] = new DatexVehicleConditionView($exemptedVehicleType, isExempted: true);
        }

        return $vehicleConditions;
    }

    public function build(): DatexTrafficRegulationView
    {
        return new DatexTrafficRegulationView(
            $this->type,
            $this->locations,
            $this->vehicleConditions,
            $this->validityConditions,
            $this->maxSpeed,
        );
    }
}
