<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Repository\OrganizationRepositoryInterface;

final class GetOrganizationsQueryHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(GetOrganizationsQuery $query): array
    {
        return $this->organizationRepository->findAll();
    }
}
