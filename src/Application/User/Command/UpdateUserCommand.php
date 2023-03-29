<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\User;

class UpdateUserCommand implements CommandInterface
{
    public User $user;
    public string $fullName;
    public string $email;
    public string $password;
}
