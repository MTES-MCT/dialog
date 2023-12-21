<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Organization;

interface OrganizationRepositoryInterface
{
    public function findOneByUuid(string $uuid): ?Organization;

    public function findOneBySiret(string $siret): ?Organization;

    public function countOrganizations(): int;

    public function add(Organization $organization): void;
}
