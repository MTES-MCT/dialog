<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum DirectionEnum: string
{
    case BOTH = 'BOTH';
    case A_TO_B = 'A_TO_B';
    case B_TO_A = 'B_TO_A';
}
