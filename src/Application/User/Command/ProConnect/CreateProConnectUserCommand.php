<?php

declare(strict_types=1);

namespace App\Application\User\Command\ProConnect;

use App\Application\CommandInterface;

final readonly class CreateProConnectUserCommand implements CommandInterface
{
    public function __construct(
        public string $email,
        public array $userInfo,
    ) {
    }
}
