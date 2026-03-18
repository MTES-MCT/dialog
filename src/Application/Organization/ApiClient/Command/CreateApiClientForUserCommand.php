<?php

declare(strict_types=1);

namespace App\Application\Organization\ApiClient\Command;

use App\Application\CommandInterface;

final class CreateApiClientForUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $organizationUuid,
        public readonly string $userUuid,
    ) {
    }
}
