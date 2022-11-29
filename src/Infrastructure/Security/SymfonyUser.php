<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SymfonyUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $uuid,
        private string $email,
        private string $password,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }
}
