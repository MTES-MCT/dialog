<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\User\View\UserOrganizationView;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SymfonyUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $uuid,
        private string $email,
        private string $fullName,
        private string $password,
        /** @var UserOrganizationView[] */
        private array $userOrganizations,
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

    /** @return UserOrganizationView[] */
    public function getUserOrganizations(): array
    {
        return $this->userOrganizations;
    }

    public function getUserOrganizationUuids(): array
    {
        $uuids = [];

        foreach ($this->userOrganizations as $org) {
            $uuids[] = $org->uuid;
        }

        return $uuids;
    }

    public function eraseCredentials(): void
    {
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setfullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }
}
