<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationOrderCategoryEnum: string
{
    case PERMANENT_REGULATION = 'permanentRegulation';
    case TEMPORARY_REGULATION = 'temporaryRegulation';
}
