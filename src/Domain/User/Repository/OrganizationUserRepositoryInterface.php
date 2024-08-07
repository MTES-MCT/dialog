<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\OrganizationUser;
use App\Domain\User\User;

interface OrganizationUserRepositoryInterface
{
    public function add(OrganizationUser $organizationUser): void;

    public function remove(OrganizationUser $organizationUser): void;

    public function findOrganizationsByUser(User $user): array;

    public function findUsersByOrganizationUuid(string $uuid): array;

    public function findOrganizationUser(string $organizationUuid, string $userUuid): ?OrganizationUser;

    public function findByEmailAndOrganization(string $email, string $organizationUuid): ?OrganizationUser;
}
