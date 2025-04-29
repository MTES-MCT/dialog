<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Command;

use App\Application\CommandInterface;

final class DeleteMailingListCommand implements CommandInterface
{
    public function __construct(
        public readonly string $uuid,
    ) {
    }
}
