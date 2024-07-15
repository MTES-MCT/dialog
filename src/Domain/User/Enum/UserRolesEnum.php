<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

enum UserRolesEnum: string
{
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    case ROLE_USER = 'ROLE_USER';
}
