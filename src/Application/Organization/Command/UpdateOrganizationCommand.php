<?php

namespace App\Application\Organization\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;

class UpdateOrganizationCommand implements CommandInterface
{
    public function __construct(
        public readonly string $organizationUuid,
        public string $name,)
    {
    }
}
