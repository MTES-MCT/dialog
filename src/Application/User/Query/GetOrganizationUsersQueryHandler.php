<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Application\User\View\OrganizationUserView;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;

final class GetOrganizationUsersQueryHandler
{
    public function __construct(
        private OrganizationUserRepositoryInterface $organizationUserRepository,
    ) {
    }

    public function __invoke(GetOrganizationUsersQuery $query): array
    {
        $organizationUsers = $this->organizationUserRepository->findByOrganizationUuid($query->organizationUuid);

        $organizationUserViews = [];

        foreach ($organizationUsers as $organizationUser) {
            $user = $organizationUser->getUser();

            $organizationUserViews[] = new OrganizationUserView(
                uuid: $user->getUuid(),
                fullName: $user->getFullName(),
                email: $user->getEmail(),
                roles: $organizationUser->getRoles(),
            );
        }

        return $organizationUserViews;
    }
}
