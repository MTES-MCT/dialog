<?php

declare(strict_types=1);

namespace App\Domain\User;

class PasswordUser
{
    public function __construct(
        private string $uuid,
        private string $password,
        private User $user,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
