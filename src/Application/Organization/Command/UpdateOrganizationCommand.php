<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\CommandInterface;

class UpdateOrganizationCommand implements CommandInterface
{
    public function __construct(
        public readonly string $organizationUuid,
        public string $name, )
    {
    }
}
