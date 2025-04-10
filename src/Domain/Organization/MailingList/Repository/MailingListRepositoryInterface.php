<?php

declare(strict_types=1);

namespace App\Domain\Organization\MailingList\Repository;

interface MailingListRepositoryInterface
{
    public function findRecipientsByOrganizationUuid(string $uuid): array;
}
