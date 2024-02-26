<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;

final class GetOrganizationBySiretQueryHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(GetOrganizationBySiretQuery $query): Organization
    {
        $organization = $this->organizationRepository->findOneBySiret(
            $query->siret,
        );

        if (!$organization instanceof Organization) {
            throw new OrganizationNotFoundException();
        }

        return $organization;
    }
}
