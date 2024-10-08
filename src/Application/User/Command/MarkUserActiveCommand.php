<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\User;

final class MarkUserActiveCommand implements CommandInterface
{
    public function __construct(
        public readonly User $user,
    ) {
    }
}
