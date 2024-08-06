<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

final class DeleteOrganizationUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
    ) {
    }

    public function __invoke(DeleteOrganizationUserCommand $command): void
    {
        $user = $command->organizationUser->getUser();
        $userOrganizations = $this->organizationUserRepository->findOrganizationsByUser($user);

        if (\count($userOrganizations) === 1) {
            // User belongs to only one organization, he is totaly removed
            $this->userRepository->remove($user);
        } else {
            // User belongs to several organizations, he is removed from the organization
            $this->organizationUserRepository->remove($command->organizationUser);
        }
    }
}
