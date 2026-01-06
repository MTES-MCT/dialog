<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Invitation;
use App\Domain\User\Repository\InvitationRepositoryInterface;

final class GetInvitationByUuidQueryHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
    ) {
    }

    public function __invoke(GetInvitationByUuidQuery $query): Invitation
    {
        $invitation = $this->invitationRepository->findOneByUuid($query->uuid);

        if (!$invitation instanceof Invitation) {
            throw new InvitationNotFoundException();
        }

        return $invitation;
    }
}
