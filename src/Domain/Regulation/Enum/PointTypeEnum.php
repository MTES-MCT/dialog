<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum PointTypeEnum: string
{
    case HOUSE_NUMBER = 'houseNumber';
    case INTERSECTION = 'intersection';
}
