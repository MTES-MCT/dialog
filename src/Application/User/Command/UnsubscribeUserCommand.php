<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;

final readonly class UnsubscribeUserCommand implements CommandInterface
{
    public function __construct(
        public string $email,
    ) {
    }
}
