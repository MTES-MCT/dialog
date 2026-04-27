<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;

final class DatexVehicleConditionView
{
    public ?string $type = null;
    public ?string $nonVehicularRoadUser = null;
    public ?string $emissionClassificationOther = null;
    public ?string $vehicleUsage = null;
    public ?string $accessConditionType = null;
    public bool $isDangerousSubstances = false;
    public bool $isOther = false;

    public function __construct(
        string $vehicleType,
        public readonly ?float $maxWeight = null,
        public readonly ?float $maxWidth = null,
        public readonly ?float $maxLength = null,
        public readonly ?float $maxHeight = null,
        public readonly bool $isExempted = false,
        public readonly ?string $otherTypeText = null,
    ) {
        switch ($vehicleType) {
            case VehicleTypeEnum::PEDESTRIANS->value:
                $this->nonVehicularRoadUser = $vehicleType;
                break;
            case VehicleTypeEnum::EMERGENCY_SERVICES->value:
                $this->vehicleUsage = $vehicleType;
                break;
            case VehicleTypeEnum::POLICE->value:
                // No dedicated DATEX II value for police: collapse to emergencyServices (police is part of emergency services).
                $this->vehicleUsage = VehicleTypeEnum::EMERGENCY_SERVICES->value;
                break;
            case VehicleTypeEnum::ROAD_MAINTENANCE_OR_CONSTRUCTION->value:
                $this->vehicleUsage = $vehicleType;
                break;
            case VehicleTypeEnum::DESSERTE_LOCALE->value:
                $this->accessConditionType = 'destinationTraffic';
                break;
            case CritairEnum::CRITAIR_0->value:
            case CritairEnum::CRITAIR_1->value:
            case CritairEnum::CRITAIR_2->value:
            case CritairEnum::CRITAIR_3->value:
            case CritairEnum::CRITAIR_4->value:
            case CritairEnum::CRITAIR_5->value:
                $this->emissionClassificationOther = $vehicleType;
                break;
            case VehicleTypeEnum::OTHER->value:
                $this->isOther = true;
                break;
            case VehicleTypeEnum::HAZARDOUS_MATERIALS->value:
                $this->isDangerousSubstances = true;
                break;
            default:
                $this->type = $vehicleType;
        }
    }
}
