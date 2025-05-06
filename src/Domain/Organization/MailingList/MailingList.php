<?php

declare(strict_types=1);

namespace App\Domain\Organization\MailingList;

use App\Domain\User\Organization;

class MailingList
{
    public function __construct(
        private string $uuid,
        private string $name,
        private string $email,
        private Organization $organization,
        private ?string $role = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function update(
        string $name,
        string $email,
        string $role,
    ): void {
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
    }
}
