<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\OrganizationUser;

interface OrganizationUserRepositoryInterface
{
    public function add(OrganizationUser $organizationUser): void;

    public function remove(OrganizationUser $organizationUser): void;

    /** @return OrganizationUser[] */
    public function findByUserUuid(string $userUuid): array;

    /** @return OrganizationUser[] */
    public function findByOrganizationUuid(string $uuid): array;

    public function findOrganizationUser(string $organizationUuid, string $userUuid): ?OrganizationUser;

    public function findByEmailAndOrganization(string $email, string $organizationUuid): ?OrganizationUser;
}
