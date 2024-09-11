<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\User;

final class SavePasswordCommand implements CommandInterface
{
    public string $password;

    public function __construct(
        public User $user,
    ) {
        $this->password = $user->getPassword();
    }
}
