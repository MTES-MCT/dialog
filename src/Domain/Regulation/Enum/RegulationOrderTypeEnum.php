<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationOrderTypeEnum: string
{
    case PERMANENT = 'permanent';
    case TEMPORARY = 'temporary';
}
