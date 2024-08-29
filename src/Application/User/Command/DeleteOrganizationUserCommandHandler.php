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
        $organizationUsers = $this->organizationUserRepository->findByUserUuid($user->getUuid());

        if (\count($organizationUsers) === 1) {
            // User belongs to only one organization, remove them and the organization user will be deleted too by CASCADE
            $this->userRepository->remove($user);
        } else {
            // User belongs to several organizations, remove only their presence in this organization
            $this->organizationUserRepository->remove($command->organizationUser);
        }
    }
}
