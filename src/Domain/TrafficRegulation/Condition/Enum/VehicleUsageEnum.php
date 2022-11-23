<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition\Enum;

enum VehicleUsageEnum: string
{
    case AGRICULTURAL = 'agricultural'; // Vehicle used for agricultural purposes.
    case CAR_SHARING = 'carSharing'; // Vehicles operated by a car-sharing company.
    case CITY_LOGISTICS = 'cityLogistics'; // Vehicles that are used to deliver goods in a city area.
    case COMMERCIAL = 'commercial'; // Vehicle which is limited to non-private usage or public transport usage.
    case EMERGENCY_SERVICES = 'emergencyServices'; // Vehicle used by the emergency services.
    case MILITARY = 'military'; // Vehicle used by the military.
    case NON_COMMERCIAL = 'nonCommercial'; // Vehicle used for non-commercial or private purposes.
    case PATROL = 'patrol'; // Vehicle used as part of a patrol service, e.g. road operator or automobile association patrol vehicle.
    case RECOVERY_SERVICES = 'recoveryServices'; // Vehicle used to provide a recovery service.
    case ROAD_MAINTENANCE_OR_CONSTRUCTION = 'roadMaintenanceOrConstruction'; // Vehicle used for road maintenance or construction work purposes.
    case ROAD_OPERATOR = 'roadOperator'; // Vehicle used by the road operator.
    case TAXI = 'taxi'; // Vehicle used to provide an authorised taxi service.
}
