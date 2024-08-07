<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\OrganizationUser;

final class DeleteOrganizationUserCommand implements CommandInterface
{
    public function __construct(
        public readonly OrganizationUser $organizationUser,
    ) {
    }
}
