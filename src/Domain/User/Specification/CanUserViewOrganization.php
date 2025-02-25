<?php

declare(strict_types=1);

namespace App\Domain\User\Specification;

use App\Domain\User\Organization;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;

class CanUserViewOrganization
{
    public function isSatisfiedBy(Organization $organization, AbstractAuthenticatedUser $user): bool
    {
        return \in_array($organization->getUuid(), $user->getUserOrganizationUuids());
    }
}
