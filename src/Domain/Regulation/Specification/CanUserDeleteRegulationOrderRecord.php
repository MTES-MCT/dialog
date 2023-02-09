<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationOrderRecord;

class CanUserDeleteRegulationOrderRecord
{
    public function isSatisfiedBy(array $userOrganizationUuids, RegulationOrderRecord $regulationOrderRecord): bool
    {
        return \in_array(
            $regulationOrderRecord->getOrganization()->getUuid(),
            $userOrganizationUuids,
            true,
        );
    }
}
