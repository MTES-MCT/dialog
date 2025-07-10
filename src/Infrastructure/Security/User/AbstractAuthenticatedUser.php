<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\User;

use App\Application\User\View\UserOrganizationView;
use App\Domain\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractAuthenticatedUser implements UserInterface
{
    protected string $uuid;
    protected string $email;
    protected string $fullName;
    protected array $userOrganizations;
    protected array $roles;

    public function __construct(
        User $user,
        array $userOrganizations,
    ) {
        $this->uuid = $user->getUuid();
        $this->email = $user->getEmail();
        $this->fullName = $user->getFullName();
        $this->roles = $user->getRoles();
        $this->userOrganizations = $userOrganizations;
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

    public function isOrganizationsCompleted(): bool
    {
        foreach ($this->userOrganizations as $org) {
            if (!$org->completed) {
                return false;
            }
        }

        return true;
    }

    public function eraseCredentials(): void
    {
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    abstract public function isVerified(): bool;

    abstract public function getAuthOrigin(): string;
}
