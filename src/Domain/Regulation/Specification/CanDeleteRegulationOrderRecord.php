<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

class CanDeleteRegulationOrderRecord
{
    public function isSatisfiedBy(Organization $organization, RegulationOrderRecord $regulationOrderRecord): bool
    {
        return $organization->getUuid() === $regulationOrderRecord->getOrganization()->getUuid();
    }
}
