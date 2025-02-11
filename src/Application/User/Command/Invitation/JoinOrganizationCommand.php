<?php

declare(strict_types=1);

namespace App\Application\User\Command\Invitation;

use App\Application\CommandInterface;
use App\Domain\User\User;

final readonly class JoinOrganizationCommand implements CommandInterface
{
    public function __construct(
        public string $invitationUuid,
        public User $user,
    ) {
    }
}
