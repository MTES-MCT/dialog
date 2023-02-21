<?php

declare(strict_types=1);

namespace App\Domain\Condition\Enum;

enum VehicleUsageEnum: string
{
    public const AGRICULTURAL = 'agricultural'; // Vehicle used for agricultural purposes.
    public const CAR_SHARING = 'carSharing'; // Vehicles operated by a car-sharing company.
    public const CITY_LOGISTICS = 'cityLogistics'; // Vehicles that are used to deliver goods in a city area.
    public const COMMERCIAL = 'commercial'; // Vehicle which is limited to non-private usage or public transport usage.
    public const EMERGENCY_SERVICES = 'emergencyServices'; // Vehicle used by the emergency services.
    public const MILITARY = 'military'; // Vehicle used by the military.
    public const NON_COMMERCIAL = 'nonCommercial'; // Vehicle used for non-commercial or private purposes.
    public const PATROL = 'patrol'; // Vehicle used as part of a patrol service, e.g. road operator or automobile association patrol vehicle.
    public const RECOVERY_SERVICES = 'recoveryServices'; // Vehicle used to provide a recovery service.
    public const ROAD_MAINTENANCE_OR_CONSTRUCTION = 'roadMaintenanceOrConstruction'; // Vehicle used for road maintenance or construction work purposes.
    public const ROAD_OPERATOR = 'roadOperator'; // Vehicle used by the road operator.
    public const TAXI = 'taxi'; // Vehicle used to provide an authorised taxi service.
}
