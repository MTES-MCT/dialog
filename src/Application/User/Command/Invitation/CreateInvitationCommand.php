<?php

declare(strict_types=1);

namespace App\Application\User\Command\Invitation;

use App\Application\CommandInterface;
use App\Domain\User\Organization;
use App\Domain\User\User;

final class CreateInvitationCommand implements CommandInterface
{
    public function __construct(
        public Organization $organization,
        public User $owner,
    ) {
    }

    public ?string $fullName = null;
    public ?string $email = null;
    public ?string $role = null;
}
