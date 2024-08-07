<?php

declare(strict_types=1);

namespace App\Domain\User\Specification;

use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;

final class IsUserAlreadyRegisteredInOrganization
{
    public function __construct(
        private readonly OrganizationUserRepositoryInterface $organizationUserRepository,
    ) {
    }

    public function isSatisfiedBy(string $email, Organization $organization): bool
    {
        return $this->organizationUserRepository
            ->findByEmailAndOrganization($email, $organization->getUuid()) instanceof OrganizationUser;
    }
}
