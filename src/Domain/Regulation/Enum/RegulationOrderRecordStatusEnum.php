<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationOrderRecordStatusEnum: string
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';

    public static function values(): array
    {
        return [self::DRAFT, self::PUBLISHED];
    }
}
