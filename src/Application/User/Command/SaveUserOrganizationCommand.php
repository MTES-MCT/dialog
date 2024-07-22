<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\User;

final class SaveUserOrganizationCommand implements CommandInterface
{
    public array $roles = [];

    public function __construct(
        public readonly User $user,
        public readonly Organization $organization,
        public readonly ?OrganizationUser $organizationUser = null,
    ) {
        $this->roles = $organizationUser?->getRoles() ?? [];
    }
}
