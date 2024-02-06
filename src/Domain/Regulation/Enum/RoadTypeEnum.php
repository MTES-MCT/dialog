<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RoadTypeEnum: string
{
    case LANE = 'lane';
    case DEPARTMENTAL_ROAD = 'departmentalRoad';
}
