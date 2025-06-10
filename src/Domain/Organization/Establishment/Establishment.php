<?php

declare(strict_types=1);

namespace App\Domain\Organization\Establishment;

use App\Domain\User\Organization;

class Establishment
{
    public function __construct(
        private string $uuid,
        private string $address,
        private string $zipCode,
        private string $city,
        private Organization $organization,
        private ?string $addressComplement = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getAddressComplement(): ?string
    {
        return $this->addressComplement;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function update(
        string $address,
        string $zipCode,
        string $city,
        ?string $addressComplement = null,
    ): void {
        $this->address = $address;
        $this->zipCode = $zipCode;
        $this->city = $city;
        $this->addressComplement = $addressComplement;
    }

    public function __toString(): string
    {
        return \sprintf('%s %s %s', $this->address, $this->zipCode, $this->city);
    }
}
