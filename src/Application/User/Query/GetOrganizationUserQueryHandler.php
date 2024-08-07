<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Exception\OrganizationUserNotFoundException;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;

final class GetOrganizationUserQueryHandler
{
    public function __construct(
        private OrganizationUserRepositoryInterface $organizationUserRepository,
    ) {
    }

    public function __invoke(GetOrganizationUserQuery $query): OrganizationUser
    {
        $organizationUser = $this->organizationUserRepository
            ->findOrganizationUser($query->organizationUuid, $query->userUuid);

        if (!$organizationUser) {
            throw new OrganizationUserNotFoundException();
        }

        return $organizationUser;
    }
}
