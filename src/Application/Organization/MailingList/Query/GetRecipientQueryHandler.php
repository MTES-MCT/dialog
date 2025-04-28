<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Query;

use App\Domain\Organization\MailingList\MailingList;
use App\Domain\Organization\MailingList\Repository\MailingListRepositoryInterface;

final class GetRecipientQueryHandler
{
    public function __construct(
        private MailingListRepositoryInterface $mailingListRepository,
    ) {
    }

    public function __invoke(GetRecipientQuery $query): ?MailingList
    {
        return $this->mailingListRepository->findOneByUuid($query->mailingListUuid);
    }
}
