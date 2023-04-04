<?php

declare(strict_types=1);

namespace App\Application\Organization\Query;

use App\Application\Organization\View\OrganizationListView;
use App\Domain\Organization\Repository\OrganizationRepositoryInterface;

class GetOrganizationsQueryHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetOrganizationsQuery $query): array
    {
        $organizations = $this->repository->findOrganizations();
        $data = [];
        foreach ($organizations as $organization) {
            $data[] = new OrganizationListView(
                $organization->getUuid(),
                strtoupper($organization->getName()),
            );
        }
        // dd($data);
        return $data;
    }
}
