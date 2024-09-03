<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\User;

final class SaveProfileCommand implements CommandInterface
{
    public ?string $fullName = null;
    public ?string $email = null;
    public ?string $password = null;

    public function __construct(
        public User $user,
    ) {
        $this->fullName = $user->getFullName();
        $this->email = $user->getEmail();
    }
}
