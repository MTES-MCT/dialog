<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\User;

use App\Domain\User\Organization;

interface OrganizationAwareUserInterface
{
    public function getOrganization(): Organization;

    public function getUserIdentifier(): string;
}
