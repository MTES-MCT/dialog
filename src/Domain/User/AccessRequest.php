<?php

declare(strict_types=1);

namespace App\Domain\User;

class AccessRequest
{
    public function __construct(
        private string $uuid,
        private string $fullName,
        private string $email,
        private string $organization,
        private string $password,
        private bool $consentToBeContacted,
        private ?string $siret = null,
        private ?string $comment = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getOrganization(): string
    {
        return $this->organization;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function isConsentToBeContacted(): bool
    {
        return $this->consentToBeContacted;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setOrganization(string $organization): void
    {
        $this->organization = $organization;
    }

    public function setSiret(string $siret): void
    {
        $this->siret = $siret;
    }
}
