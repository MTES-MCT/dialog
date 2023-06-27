<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Organization;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SymfonyUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $uuid,
        private string $email,
        private string $fullName,
        private string $password,
        private array $organizations,
        private array $roles,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getOrganizations(): array
    {
        return $this->organizations;
    }

    public function getOrganizationUuids(): array
    {
        return array_map(
            function (Organization $organization) { return $organization->getUuid(); },
            $this->organizations,
        );
    }

    public function eraseCredentials(): void
    {
    }
}
