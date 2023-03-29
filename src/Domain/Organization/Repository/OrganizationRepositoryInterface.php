<?php

declare(strict_types=1);

namespace App\Domain\Organization\Repository;

use App\Domain\User\Organization;

interface OrganizationRepositoryInterface
{
    public function findOrganizations(): array;

    public function save(Organization $organization): Organization;

    public function findByUuid(string $uuid): Organization|null;
}
