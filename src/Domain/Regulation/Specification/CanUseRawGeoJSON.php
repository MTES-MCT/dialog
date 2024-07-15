<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\User\Enum\UserRolesEnum;

final class CanUseRawGeoJSON
{
    public const PERMISSION_NAME = 'canUseRawGeoJSON';

    public function isSatisfiedBy(?array $roles): bool
    {
        return \in_array(UserRolesEnum::ROLE_SUPER_ADMIN->value, $roles ?? []);
    }
}
