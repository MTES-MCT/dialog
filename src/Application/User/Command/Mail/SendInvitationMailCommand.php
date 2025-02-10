<?php

declare(strict_types=1);

namespace App\Application\User\Command\Mail;

use App\Application\AsyncCommandInterface;
use App\Domain\User\Invitation;

final class SendInvitationMailCommand implements AsyncCommandInterface
{
    public function __construct(
        public readonly Invitation $invitation,
    ) {
    }
}
