<?php

declare(strict_types=1);

namespace App\Application\Organization\ApiClient\Command;

use App\Application\CommandInterface;

final class RegenerateApiClientSecretCommand implements CommandInterface
{
    public function __construct(
        public readonly string $apiClientUuid,
    ) {
    }
}
