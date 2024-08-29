<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationOrderRecordStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}
