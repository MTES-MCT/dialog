<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\User\User;

final class CanUseRawGeoJSON
{
    public const PERMISSION_NAME = 'canUseRawGeoJSON';

    public function isSatisfiedBy(?array $roles): bool
    {
        return \in_array(User::ROLE_ADMIN, $roles ?? []);
    }
}
