<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Query;

use App\Domain\Organization\MailingList\Repository\MailingListRepositoryInterface;

final class GetMailingListQueryHandler
{
    public function __construct(
        private MailingListRepositoryInterface $mailingListRepository,
    ) {
    }

    public function __invoke(GetMailingListQuery $query): array
    {
        return $this->mailingListRepository->findRecipientsByOrganizationUuid($query->organizationUuid);
    }
}
