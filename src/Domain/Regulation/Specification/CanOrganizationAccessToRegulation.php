<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\User\OrganizationRegulationAccessInterface;

class CanOrganizationAccessToRegulation
{
    public function isSatisfiedBy(
        OrganizationRegulationAccessInterface|string $organizationUuid,
        array $organizationUuids,
    ): bool {
        if (!\is_string($organizationUuid)) {
            $organizationUuid = $organizationUuid->getOrganizationUuid();
        }

        return \in_array($organizationUuid, $organizationUuids);
    }
}
