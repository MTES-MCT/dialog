<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum MeasureTypeEnum: string
{
    case NO_ENTRY = 'noEntry';
    case ALTERNATE_ROAD = 'alternateRoad';
    case ONE_WAY_TRAFFIC = 'oneWayTraffic';
    case SPEED_LIMITATION = 'speedLimitation';
}
