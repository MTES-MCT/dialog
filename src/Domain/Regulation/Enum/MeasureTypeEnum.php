<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum MeasureTypeEnum: string
{
    case ALTERNATE = 'alternate';
    case NO_ENTRY = 'noEntry';
    case SPEED_LIMITATION = 'speedLimitation';
    case PARKING_PROHIBITED = 'parkingProhibited';
}
