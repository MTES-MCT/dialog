<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationOrderIssueLevelEnum: string
{
    case WARNING = 'warning';
    case ERROR = 'error';
}
