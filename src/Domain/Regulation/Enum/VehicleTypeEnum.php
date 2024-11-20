<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum VehicleTypeEnum: string
{
    // NOTE: this enum combines items from multiple DATEX II enums, including VehicleTypeEnum, VehicleUsageEnum, and NonVehicularRoadUserTypeEnum.
    // So it is not a 1:1 mapping with DATEX II's VehicleTypeEnum, although it shares the same name.
    case HEAVY_GOODS_VEHICLE = 'heavyGoodsVehicle'; // Vehicles with a total weight above 3,500 kg (vehicle and load)
    case DIMENSIONS = 'dimensions'; // CUSTOM: Vehicle characterized by its maximum dimensions
    case COMMERCIAL = 'commercial'; // public transport.
    case PEDESTRIANS = 'pedestrians'; // Pedestrians.
    case BICYCLE = 'bicycle'; // Bicycle.
    case EMERGENCY_SERVICES = 'emergencyServices'; // A motor vehicle of category M intended for the transport of sick or injured people and having special equipment for such purpose.
    case CRITAIR = 'critair';
    case OTHER = 'other';
    case TAXI = 'taxi';
    case CAR_SHARING = 'carSharing';
    case HAZARDOUS_MATERIALS = 'hazardousMaterials';
    case SERVICES_VEHICLES = 'servicesVehicles';

    public static function restrictedCases(): array
    {
        return [
            VehicleTypeEnum::HEAVY_GOODS_VEHICLE,
            VehicleTypeEnum::DIMENSIONS,
            VehicleTypeEnum::CRITAIR,
            VehicleTypeEnum::HAZARDOUS_MATERIALS,
            VehicleTypeEnum::OTHER,
        ];
    }

    public static function exemptedCases(): array
    {
        return [
            VehicleTypeEnum::COMMERCIAL,
            VehicleTypeEnum::EMERGENCY_SERVICES,
            VehicleTypeEnum::BICYCLE,
            VehicleTypeEnum::PEDESTRIANS,
            VehicleTypeEnum::TAXI,
            VehicleTypeEnum::CAR_SHARING,
            VehicleTypeEnum::SERVICES_VEHICLES,
            VehicleTypeEnum::OTHER,
        ];
    }
}
