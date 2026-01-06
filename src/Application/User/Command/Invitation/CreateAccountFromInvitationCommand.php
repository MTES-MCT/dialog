<?php

declare(strict_types=1);

namespace App\Application\User\Command\Invitation;

use App\Application\CommandInterface;

final class CreateAccountFromInvitationCommand implements CommandInterface
{
    public ?string $password = null;

    public function __construct(
        public readonly string $invitationUuid,
    ) {
    }
}
