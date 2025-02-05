<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Repository\InvitationRepositoryInterface;

final class GetInvitationsQueryHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
    ) {
    }

    public function __invoke(GetInvitationsQuery $query): array
    {
        return $this->invitationRepository->findByOrganizationUuid($query->organizationUuid);
    }
}
