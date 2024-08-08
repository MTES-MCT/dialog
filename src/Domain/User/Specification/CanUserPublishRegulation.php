<?php

declare(strict_types=1);

namespace App\Domain\User\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Infrastructure\Security\SymfonyUser;

class CanUserPublishRegulation
{
    public function isSatisfiedBy(RegulationOrderRecord $regulationOrderRecord, SymfonyUser $user): bool
    {
        $organization = $regulationOrderRecord->getOrganization();

        foreach ($user->getOrganizationUsers() as $userOrganization) {
            if ($userOrganization->uuid !== $organization->getUuid()) {
                continue;
            }

            return \in_array(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value, $userOrganization->roles)
                || \in_array(OrganizationRolesEnum::ROLE_ORGA_PUBLISHER->value, $userOrganization->roles);
        }

        return false;
    }
}
