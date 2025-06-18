<?php

declare(strict_types=1);

namespace App\Domain\Organization\SigningAuthority;

use App\Domain\User\Organization;

class SigningAuthority
{
    public function __construct(
        private string $uuid,
        private string $name,
        private string $role,
        private string $signatoryName,
        private Organization $organization,
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

    public function getRole(): string
    {
        return $this->role;
    }

    public function getSignatoryName(): string
    {
        return $this->signatoryName;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function update(
        string $name,
        string $role,
        string $signatoryName,
    ): void {
        $this->name = $name;
        $this->role = $role;
        $this->signatoryName = $signatoryName;
    }
}
