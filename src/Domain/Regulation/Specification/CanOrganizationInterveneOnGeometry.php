<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\User\Repository\OrganizationRepositoryInterface;

class CanOrganizationInterveneOnGeometry
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function isSatisfiedBy(string $uuid, string $geometry): bool
    {
        $organization = $this->organizationRepository->findOneByUuid($uuid);
        if (!$organization) {
            return false;
        }

        return $this->organizationRepository->canInterveneOnGeometry($uuid, $geometry);
    }
}
