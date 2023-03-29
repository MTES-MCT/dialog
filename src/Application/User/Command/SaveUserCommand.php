<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;

class SaveUserCommand implements CommandInterface
{
    public string $uuid;
    public string $fullName;
    public string $email;
    public string $password;
    public string $organizations;
}
