<?php

namespace App\Domain\Organization\Repository;

use App\Domain\User\Organization;

interface OrganizationRepositoryInterface
{
    public function findOrganizations(): array;
    public function save(Organization $organization): Organization;
    public function findByUuid(string $uuid) : Organization|null;
}