<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\User;

use App\Domain\Organization\ApiClient;
use App\Domain\User\Organization;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApiClientUser implements UserInterface, PasswordAuthenticatedUserInterface, OrganizationAwareUserInterface
{
    public function __construct(
        private readonly ApiClient $apiClient,
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_API'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->apiClient->getClientId();
    }

    public function getPassword(): ?string
    {
        return $this->apiClient->getClientSecret();
    }

    public function getOrganization(): Organization
    {
        return $this->apiClient->getOrganization();
    }
}
