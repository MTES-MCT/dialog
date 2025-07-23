<?php

declare(strict_types=1);

namespace App\Domain\User;

class User
{
    private string $fullName;
    private string $email;
    private array $roles = [];
    private \DateTimeInterface $registrationDate;
    private ?\DateTimeInterface $lastActiveAt;
    private ?PasswordUser $passwordUser = null;
    private ?ProConnectUser $proConnectUser = null;
    private bool $isVerified = false;

    public function __construct(
        private string $uuid,
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

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

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

    public function getRegistrationDate(): \DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeInterface $date): self
    {
        $this->registrationDate = $date;

        return $this;
    }

    public function getLastActiveAt(): ?\DateTimeInterface
    {
        return $this->lastActiveAt;
    }

    public function setLastActiveAt(\DateTimeInterface $date): self
    {
        $this->lastActiveAt = $date;

        return $this;
    }

    public function getProConnectUser(): ?ProConnectUser
    {
        return $this->proConnectUser;
    }

    public function setPasswordUser(PasswordUser $passwordUser): self
    {
        $this->passwordUser = $passwordUser;

        return $this;
    }

    public function setProConnectUser(ProConnectUser $proConnectUser): self
    {
        $this->proConnectUser = $proConnectUser;

        return $this;
    }

    public function getPasswordUser(): ?PasswordUser
    {
        return $this->passwordUser;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(): self
    {
        $this->isVerified = true;

        return $this;
    }

    public function __toString(): string
    {
        return \sprintf('%s (%s)', $this->fullName, $this->email);
    }
}
