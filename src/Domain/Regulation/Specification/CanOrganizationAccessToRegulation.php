<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\User\Organization;
use App\Domain\User\OrganizationRegulationAccessInterface;

class CanOrganizationAccessToRegulation
{
    public function isSatisfiedBy(
        OrganizationRegulationAccessInterface $organization,
        Organization $userOrganization,
    ): bool {
        return $organization->getOrganizationUuid() === $userOrganization->getUuid();
    }
}
