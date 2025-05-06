<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Query;

use App\Application\QueryInterface;

final class GetRecipientQuery implements QueryInterface
{
    public function __construct(
        public readonly string $mailingListUuid,
    ) {
    }
}
