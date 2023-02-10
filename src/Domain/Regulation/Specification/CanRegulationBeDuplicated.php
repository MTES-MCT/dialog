<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

class CanRegulationBeDuplicated
{
    public function isSatisfiedBy(
        RegulationOrderRecord $regulationOrderRecord,
        Organization $userOrganization,
    ): bool {
        return $regulationOrderRecord->getOrganization()->getUuid() === $userOrganization->getUuid();
    }
}
