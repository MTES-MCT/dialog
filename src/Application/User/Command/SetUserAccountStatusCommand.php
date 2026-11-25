<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;

final class SetUserAccountStatusCommand implements CommandInterface
{
    public function __construct(
        public readonly string $userUuid,
        public readonly string $accountStatus,
    ) {
    }
}
