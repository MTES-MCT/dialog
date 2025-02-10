<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Exception\InvitationNotOwnedException;
use App\Domain\User\Exception\OrganizationUserAlreadyExistException;
use App\Domain\User\Invitation;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;

final readonly class JoinOrganizationCommandHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private IdFactoryInterface $idFactory,
    ) {
    }

    public function __invoke(JoinOrganizationCommand $command): void
    {
        $invitation = $this->invitationRepository->findOneByUuid($command->invitationUuid);
        if (!$invitation instanceof Invitation) {
            throw new InvitationNotFoundException();
        }

        $user = $command->user;
        $organization = $invitation->getOrganization();

        if ($invitation->getEmail() !== $user->getEmail()) {
            throw new InvitationNotOwnedException();
        }

        $userOrganization = $this->organizationUserRepository
            ->findOrganizationUser($organization->getUuid(), $user->getUuid());

        if ($userOrganization instanceof OrganizationUser) {
            throw new OrganizationUserAlreadyExistException();
        }

        $this->organizationUserRepository->add(
            (new OrganizationUser($this->idFactory->make()))
                ->setUser($user)
                ->setOrganization($organization)
                ->setRoles($invitation->getRole()),
        );

        $this->invitationRepository->delete($invitation);
    }
}
