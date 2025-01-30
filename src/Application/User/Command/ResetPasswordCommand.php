<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;

final class ResetPasswordCommand implements CommandInterface
{
    public ?string $password = '';

    public function __construct(
        public readonly string $token,
    ) {
    }
}
