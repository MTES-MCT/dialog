<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

final class CanUseRawGeoJSON
{
    public const PERMISSION_NAME = 'canUseRawGeoJSON';

    public function isSatisfiedBy(): bool
    {
        return true;
    }
}
