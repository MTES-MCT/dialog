<?php

declare(strict_types=1);

namespace App\Domain\RegulationOrder\Enum;

enum RegulationOrderRecordStatus: string
{
    case PUBLISHED = 'published';
}
