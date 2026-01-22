<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Application\User\View\OrganizationUserView;
use App\Application\User\View\UserOrganizationView;
use App\Domain\User\OrganizationUser;

interface OrganizationUserRepositoryInterface
{
    public function add(OrganizationUser $organizationUser): void;

    public function remove(OrganizationUser $organizationUser): void;

    /** @return UserOrganizationView[] */
    public function findByUserUuid(string $userUuid): array;

    /** @return OrganizationUserView[] */
    public function findByOrganizationUuid(string $uuid): array;

    public function findOrganizationUser(string $organizationUuid, string $userUuid): ?OrganizationUser;

    public function findByEmailAndOrganization(string $email, string $organizationUuid): ?OrganizationUser;

    public function findAllUsersWithOrganizations(): array;
}
