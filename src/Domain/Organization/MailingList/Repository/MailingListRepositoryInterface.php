<?php

declare(strict_types=1);

namespace App\Domain\Organization\MailingList\Repository;

use App\Domain\Organization\MailingList\MailingList;

interface MailingListRepositoryInterface
{
    public function findRecipientsByOrganizationUuid(string $uuid): array;

    public function add(MailingList $mailingList): MailingList;
}
