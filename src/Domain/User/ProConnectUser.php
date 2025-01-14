<?php

declare(strict_types=1);

namespace App\Domain\User;

class ProConnectUser
{
    public function __construct(
        private string $uuid,
        private User $user,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
