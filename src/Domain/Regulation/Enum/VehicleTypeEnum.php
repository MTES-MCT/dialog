<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum VehicleTypeEnum: string
{
    // NOTE: this enum combines items from multiple DATEX II enums, including VehicleTypeEnum, VehicleUsageEnum, and NonVehicularRoadUserTypeEnum.
    // So it is not a 1:1 mapping with DATEX II's VehicleTypeEnum, although it shares the same name.
    case HEAVY_GOODS_VEHICLE = 'heavyGoodsVehicle'; // Vehicles with a total weight above 3,500 kg (vehicle and load)
    case BUS = 'bus'; // Bus.
    case PEDESTRIANS = 'pedestrians'; // Pedestrians.
    case BICYCLE = 'bicycle'; // Bicycle.
    case AMBULANCE = 'ambulance'; // A motor vehicle of category M intended for the transport of sick or injured people and having special equipment for such purpose.
    case CRITAIR_4 = 'critair4'; // See: https://www.legifrance.gouv.fr/loda/id/JORFTEXT000032749723/
    case CRITAIR_5 = 'critair5'; // See: https://www.legifrance.gouv.fr/loda/id/JORFTEXT000032749723/
    case OTHER = 'other';

    public static function restrictedCases(): array
    {
        return [
            VehicleTypeEnum::HEAVY_GOODS_VEHICLE,
            VehicleTypeEnum::CRITAIR_4,
            VehicleTypeEnum::CRITAIR_5,
            VehicleTypeEnum::OTHER,
        ];
    }

    public static function exemptedCases(): array
    {
        return [
            VehicleTypeEnum::BICYCLE,
            VehicleTypeEnum::BUS,
            VehicleTypeEnum::AMBULANCE,
            VehicleTypeEnum::PEDESTRIANS,
            VehicleTypeEnum::OTHER,
        ];
    }
}
