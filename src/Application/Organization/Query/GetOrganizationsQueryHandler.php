<?php

declare(strict_types=1);

namespace App\Application\Organization\Query;

use App\Domain\Organization\Repository\OrganizationRepositoryInterface;

class GetOrganizationsQueryHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetOrganizationsQuery $query): array
    {
        $organization = $this->repository->findOrganizations();

        return $organization;
    }
}
