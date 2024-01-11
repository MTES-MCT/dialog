<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum LocationTypeEnum: string
{
    case LANE = 'lane';
    case DEPARTMENTAL_ROAD = 'departmentalRoad';
    // case MOTOR_WAY = 'motorWay';
    // case NATIONAL_ROAD = 'nationalRoad';
}
