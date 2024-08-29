<?php

declare(strict_types=1);

namespace App\Domain\User\Specification;

use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Organization;
use App\Infrastructure\Security\SymfonyUser;

class CanUserEditOrganization
{
    public function isSatisfiedBy(Organization $organization, SymfonyUser $user): bool
    {
        foreach ($user->getUserOrganizations() as $userOrganization) {
            if ($userOrganization->uuid !== $organization->getUuid()) {
                continue;
            }

            return \in_array(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value, $userOrganization->roles);
        }

        return false;
    }
}
