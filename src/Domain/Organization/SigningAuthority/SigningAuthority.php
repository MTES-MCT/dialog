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
        private string $placeOfSignature,
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

    public function getPlaceOfSignature(): string
    {
        return $this->placeOfSignature;
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
        string $address,
        string $placeOfSignature,
        string $signatoryName,
    ): void {
        $this->name = $name;
        $this->address = $address;
        $this->placeOfSignature = $placeOfSignature;
        $this->signatoryName = $signatoryName;
    }
}
