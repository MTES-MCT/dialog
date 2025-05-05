<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Command;

use App\Application\AsyncCommandInterface;

final class SendRegulationOrderToMailingListCommand implements AsyncCommandInterface
{
    public readonly string $email;
}
