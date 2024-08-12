<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Repository\OrganizationRepositoryInterface;

final class GetAllOrganizationsQueryHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(GetAllOrganizationsQuery $query): array
    {
        return $this->organizationRepository->findAll();
    }
}
