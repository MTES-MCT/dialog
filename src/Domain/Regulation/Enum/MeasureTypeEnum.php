<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum MeasureTypeEnum: string
{
    public const NO_ENTRY = 'noEntry';
    public const ALTERNATE_ROAD = 'alternateRoad';
    public const ONE_WAY_TRAFFIC = 'oneWayTraffic';
}
