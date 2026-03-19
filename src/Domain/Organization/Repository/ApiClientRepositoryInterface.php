<?php

declare(strict_types=1);

namespace App\Domain\Organization\Repository;

use App\Domain\Organization\ApiClient;
use App\Domain\User\Organization;
use App\Domain\User\User;

interface ApiClientRepositoryInterface
{
    public function findOneByClientId(string $clientId): ?ApiClient;

    /**
     * @return ApiClient[]
     */
    public function findByOrganization(Organization $organization): array;

    public function findOneByOrganizationAndUser(Organization $organization, User $user): ?ApiClient;

    public function findOneByUuid(string $uuid): ?ApiClient;

    public function add(ApiClient $apiClient): void;

    public function remove(ApiClient $apiClient): void;
}
