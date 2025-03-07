<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

final class SyncOrganizationsAdministrativeBoundariesCommandResult
{
    public function __construct(
        public int $totalOrganizations,
        public int $updatedOrganizations,
    ) {
    }
}
