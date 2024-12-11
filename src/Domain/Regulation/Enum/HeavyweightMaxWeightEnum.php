<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum HeavyweightMaxWeightEnum: string
{
    case T3_5 = '3.5t';
    case T7_5 = '7.5t';
    case T19 = '19t';
    case T26 = '26t';
    case T32 = '32t';
    case T44 = '44t';
}
