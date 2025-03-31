<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\User\Repository\OrganizationRepositoryInterface;

class CanOrganizationInterveneOnGeometry
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private string $dialogOrgId,
    ) {
    }

    public function isSatisfiedBy(string $uuid, string $geometry): bool
    {
        if ($this->dialogOrgId === $uuid) {
            return true;
        }

        $organization = $this->organizationRepository->findOneByUuid($uuid);
        if (!$organization) {
            return false;
        }

        return $this->organizationRepository->canInterveneOnGeometry($uuid, $geometry);
    }
}
