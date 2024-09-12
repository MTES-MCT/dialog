<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;

final class SaveOrganizationUserCommand implements CommandInterface
{
    public ?string $fullName = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $role = null;

    public function __construct(
        public readonly Organization $organization,
        public readonly ?OrganizationUser $organizationUser = null,
    ) {
        $this->role = $organizationUser?->getRole();
        $this->fullName = $organizationUser?->getUser()?->getFullName();
        $this->email = $organizationUser?->getUser()?->getEmail();
    }
}
