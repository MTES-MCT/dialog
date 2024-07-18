<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Repository\OrganizationUserRepositoryInterface;

final class GetOrganizationUsersQueryHandler
{
    public function __construct(
        private OrganizationUserRepositoryInterface $organizationUserRepository,
    ) {
    }

    public function __invoke(GetOrganizationUsersQuery $query): array
    {
        return $this->organizationUserRepository->findUsersByOrganizationUuid($query->organizationUuid);
    }
}
