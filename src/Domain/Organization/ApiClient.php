<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\User\Organization;

class ApiClient
{
    private string $clientId;
    private string $clientSecret;
    private Organization $organization;
    private \DateTimeInterface $createdAt;

    public function __construct(
        private string $uuid,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }
}
