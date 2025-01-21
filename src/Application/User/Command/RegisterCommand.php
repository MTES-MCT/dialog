<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;

final class RegisterCommand implements CommandInterface
{
    public ?string $fullName;
    public ?string $email;
    public ?string $password;
    public ?string $organizationSiret;
}
