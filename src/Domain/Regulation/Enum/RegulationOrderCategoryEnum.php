<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationOrderCategoryEnum: string
{
    case ROAD_MAINTENANCE = 'roadMaintenance';
    case PERMANENT_REGULATION = 'permanentRegulation';
    case INCIDENT = 'incident';
    case EVENT = 'event';
    case OTHER = 'other';
}
