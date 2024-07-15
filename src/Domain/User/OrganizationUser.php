<?php

declare(strict_types=1);

namespace App\Domain\User;

class OrganizationUser
{
    private Organization $organization;
    private User $user;
    private array $roles = [];

    public function __construct(
        private string $uuid,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
}
