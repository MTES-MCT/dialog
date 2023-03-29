<?php

declare(strict_types=1);

namespace App\Domain\User;

class User
{
    public function __construct(
        private string $uuid,
        private string $fullName,
        private string $email,
        private string $password,
        private ?iterable $organizations = null,
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * For the moment we only manage the case of single organization
     */
    public function getOrganization(): ?Organization
    {
        if (!$this->organizations) {
            return null;
        }

        return $this->organizations[0];
    }

    public function update(
        string $fullName,
        string $email,
        string $password)
    {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->password = $password;
    }
}
