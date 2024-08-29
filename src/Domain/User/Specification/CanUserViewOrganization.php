<?php

declare(strict_types=1);

namespace App\Domain\User\Specification;

use App\Domain\User\Organization;
use App\Infrastructure\Security\SymfonyUser;

class CanUserViewOrganization
{
    public function isSatisfiedBy(Organization $organization, SymfonyUser $user): bool
    {
        return \in_array($organization->getUuid(), $user->getUserOrganizationUuids());
    }
}
