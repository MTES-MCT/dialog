<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;

final class SaveUserOrganizationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
    ) {
    }

    public function __invoke(SaveUserOrganizationCommand $command): void
    {
        if (!$command->organizationUser) {
            $this->organizationUserRepository->add(
                (new OrganizationUser($this->idFactory->make()))
                    ->setUser($command->user)
                    ->setOrganization($command->organization)
                    ->setRoles($command->roles),
            );

            return;
        }

        $command->organizationUser->setRoles($command->roles);
    }
}
