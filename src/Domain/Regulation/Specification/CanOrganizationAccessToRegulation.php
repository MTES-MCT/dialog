<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\User\OrganizationRegulationAccessInterface;

class CanOrganizationAccessToRegulation
{
    public function isSatisfiedBy(
        OrganizationRegulationAccessInterface $organization,
        array $userOrganizationUuids,
    ): bool {
        return \in_array($organization->getOrganizationUuid(), $userOrganizationUuids);
    }
}
