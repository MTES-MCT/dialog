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
}
