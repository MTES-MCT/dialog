<?php

declare(strict_types=1);

namespace App\Application\User\Command\Invitation;

use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Exception\InvitationNotOwnedException;
use App\Domain\User\Invitation;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use App\Domain\User\Specification\CanUserEditOrganization;

final readonly class DeleteInvitationCommandHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private CanUserEditOrganization $canUserEditOrganization,
    ) {
    }

    public function __invoke(DeleteInvitationCommand $command): string
    {
        $invitation = $this->invitationRepository->findOneByUuid($command->invitationUuid);
        if (!$invitation instanceof Invitation) {
            throw new InvitationNotFoundException();
        }

        $organizationUuid = $invitation->getOrganization()->getUuid();

        if (!$this->canUserEditOrganization->isSatisfiedBy($invitation->getOrganization(), $command->user)) {
            throw new InvitationNotOwnedException();
        }

        $this->invitationRepository->delete($invitation);

        return $organizationUuid;
    }
}
