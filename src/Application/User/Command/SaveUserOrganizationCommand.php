<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;

final class SaveUserOrganizationCommand implements CommandInterface
{
    public ?string $fullName = null;
    public ?string $email = null;
    public ?string $password = null;
    public array $roles = [];

    public function __construct(
        public readonly Organization $organization,
        public readonly ?OrganizationUser $organizationUser = null,
    ) {
        $this->roles = $organizationUser?->getRoles() ?? [];
        $this->fullName = $organizationUser?->getUser()?->getFullName();
        $this->email = $organizationUser?->getUser()?->getEmail();
    }
}
