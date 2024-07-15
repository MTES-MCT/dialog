<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

enum OrganizationRolesEnum: string
{
    case ROLE_ORGA_ADMIN = 'ROLE_ORGA_ADMIN';
    case ROLE_ORGA_CONTRIBUTOR = 'ROLE_ORGA_CONTRIBUTOR';
    case ROLE_ORGA_PUBLISHER = 'ROLE_ORGA_PUBLISHER';
}
