<?php

namespace App\Application\Organization\Query;

use App\Domain\Organization\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Organization;

class GetOrganizationByUuidQueryHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetOrganizationByUuidQuery $query) : Organization|null
    {
        // dd($query);
        $organization = $this->repository->findByUuid($query->uuid);
        return $organization;
    }
}