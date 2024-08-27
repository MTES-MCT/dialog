<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Application\User\View\OrganizationView;
use App\Domain\User\Repository\OrganizationRepositoryInterface;

final class GetOrganizationsQueryHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(GetOrganizationsQuery $query): array
    {
        $organizations = $this->organizationRepository->findAll();

        $views = [];

        foreach ($organizations as $organization) {
            $views[] = new OrganizationView(
                uuid: $organization->getUuid(),
                name: $organization->getName(),
            );
        }

        return $views;
    }
}
