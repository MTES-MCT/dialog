<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Command;

use App\Application\CommandInterface;
use App\Domain\Organization\MailingList\MailingList;
use App\Domain\User\Organization;

final class SaveMailingListCommand implements CommandInterface
{
    public ?string $name;
    public ?string $email;
    public ?string $role;

    public function __construct(
        public Organization $organization,
        public ?MailingList $mailingList = null,
    ) {
        $this->name = $mailingList?->getName();
        $this->email = $mailingList?->getEmail();
        $this->role = $mailingList?->getRole();
    }
}
