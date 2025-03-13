<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\AsyncCommandInterface;

final class SyncOrganizationAdministrativeBoundariesCommand implements AsyncCommandInterface
{
    public function __construct(
        public readonly string $organizationUuid,
    ) {
    }
}
