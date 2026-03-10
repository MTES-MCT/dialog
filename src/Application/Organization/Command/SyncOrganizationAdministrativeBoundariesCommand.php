<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\AsyncCommandInterface;
use App\Application\CommandInterface;

final class SyncOrganizationAdministrativeBoundariesCommand implements CommandInterface, AsyncCommandInterface
{
    public function __construct(
        public readonly string $organizationUuid,
    ) {
    }
}
