<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition\Enum;

enum VehicleCritairEnum: string
{
    case V1 = 'V1';
    case V2 = 'V2';
    case V3 = 'V3';
    case V4 = 'V4';
    case V5 = 'V5';
    case EC = 'EC';
    case EL = 'EL';
}
