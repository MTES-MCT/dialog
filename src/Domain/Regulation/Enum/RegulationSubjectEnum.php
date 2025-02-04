<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationSubjectEnum: string
{
    case ROAD_MAINTENANCE = 'roadMaintenance';
    case INCIDENT = 'incident';
    case EVENT = 'event';
    case WINTER_MAINTENANCE = 'winterMaintenance';
    case OTHER = 'other';
}
