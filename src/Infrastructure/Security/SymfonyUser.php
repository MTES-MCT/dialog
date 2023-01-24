<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SymfonyUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $uuid,
        private string $identifier,
        private array $roles,
        private ?string $password = null,
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
        return $this->identifier;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }

    public static function createFromPayload($username, array $payload): self
    {
        return new self($payload['uuid'], $username, $payload['roles']);
    }
}
