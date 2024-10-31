<?php

declare(strict_types=1);

namespace App\Domain\Organization\SigningAuthority;

use App\Domain\User\Organization;

class SigningAuthority
{
    public function __construct(
        private string $uuid,
        private string $name,
        private string $address,
        private string $madeIn,
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

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getMadeIn(): string
    {
        return $this->madeIn;
    }

    public function getSignatoryName(): string
    {
        return $this->signatoryName;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }
}
