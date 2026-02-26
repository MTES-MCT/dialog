<?php

declare(strict_types=1);

namespace App\Domain\User\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;

class CanUserPublishRegulation
{
    public function isSatisfiedBy(RegulationOrderRecord $regulationOrderRecord, AbstractAuthenticatedUser $user): bool
    {
        $organization = $regulationOrderRecord->getOrganization();

        return \in_array($organization->getUuid(), $user->getUserOrganizationUuids());
    }
}
