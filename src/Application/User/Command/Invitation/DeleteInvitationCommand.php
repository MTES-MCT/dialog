<?php

declare(strict_types=1);

namespace App\Application\User\Command\Invitation;

use App\Application\CommandInterface;
use App\Infrastructure\Security\SymfonyUser;

final readonly class DeleteInvitationCommand implements CommandInterface
{
    public function __construct(
        public string $invitationUuid,
        public SymfonyUser $user,
    ) {
    }
}
