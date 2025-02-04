<?php

declare(strict_types=1);

namespace App\Domain\User;

class Invitation
{
    public function __construct(
        private string $uuid,
        private string $email,
        private string $fullName,
        private string $role,
        private \DateTimeInterface $createdAt,
        private User $owner,
        private Organization $organization,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }
}
