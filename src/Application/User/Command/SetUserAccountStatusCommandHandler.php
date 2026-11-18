<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Enum\UserAccountStatusEnum;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;

final readonly class SetUserAccountStatusCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
    ) {
    }

    public function __invoke(SetUserAccountStatusCommand $command): void
    {
        $status = UserAccountStatusEnum::tryFrom($command->accountStatus);
        if (!$status) {
            throw new \InvalidArgumentException(\sprintf('Invalid account status "%s".', $command->accountStatus));
        }

        $user = $this->userRepository->findOneByUuid($command->userUuid);
        if (!$user instanceof User) {
            throw new UserNotFoundException();
        }

        $user->setAccountStatus($status->value);

        // Ownership is only granted to validated accounts, so that a pending or refused
        // self-registration cannot hold the owner role of an organization.
        foreach ($this->organizationUserRepository->findByUserUuid($user->getUuid()) as $userOrganization) {
            if ($status === UserAccountStatusEnum::VALIDATED) {
                if ($userOrganization->isOwner || $this->organizationUserRepository->countOwnersByOrganizationUuid($userOrganization->uuid) > 0) {
                    continue;
                }

                $this->organizationUserRepository->findOrganizationUser($userOrganization->uuid, $user->getUuid())?->setIsOwner(true);
            } elseif ($userOrganization->isOwner) {
                $this->organizationUserRepository->findOrganizationUser($userOrganization->uuid, $user->getUuid())?->setIsOwner(false);
            }
        }
    }
}
