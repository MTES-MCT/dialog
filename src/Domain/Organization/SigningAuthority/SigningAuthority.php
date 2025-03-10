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
        private ?string $roadName = null,
        private ?string $cityCode = null,
        private ?string $cityLabel = null,
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

    public function getRoadName(): ?string
    {
        return $this->roadName;
    }

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    public function getCityLabel(): ?string
    {
        return $this->cityLabel;
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
        ?string $roadName,
        ?string $cityCode,
        ?string $cityLabel,
    ): void {
        $this->name = $name;
        $this->address = $address;
        $this->placeOfSignature = $placeOfSignature;
        $this->signatoryName = $signatoryName;
        $this->roadName = $roadName;
        $this->cityCode = $cityCode;
        $this->cityLabel = $cityLabel;
    }
}
