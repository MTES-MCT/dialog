<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;

final class DatexVehicleConditionView
{
    public ?string $type = null;
    public ?string $nonVehicularRoadUser = null;
    public ?string $euSpecialPurposeVehicle = null;
    public ?string $emissionClassificationOther = null;
    public bool $isOther = false;

    public function __construct(
        string $vehicleType,
        public readonly bool $isExempted = false,
    ) {
        switch ($vehicleType) {
            case VehicleTypeEnum::PEDESTRIANS->value:
                $this->nonVehicularRoadUser = $vehicleType;
                break;
            case VehicleTypeEnum::AMBULANCE->value:
                $this->euSpecialPurposeVehicle = $vehicleType;
                break;
            case CritairEnum::CRITAIR_2->value:
            case CritairEnum::CRITAIR_3->value:
            case CritairEnum::CRITAIR_4->value:
            case CritairEnum::CRITAIR_5->value:
                $this->emissionClassificationOther = $vehicleType;
                break;
            case VehicleTypeEnum::OTHER->value:
                $this->isOther = true;
                break;
            default:
                $this->type = $vehicleType;
        }
    }
}
