<?php

declare(strict_types=1);

namespace App\Domain\User;

class Token
{
    public function __construct(
        private string $uuid,
        private string $token,
        private string $type,
        private User $user,
        private \DateTimeInterface $expirationDate,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getExpirationDate(): \DateTimeInterface
    {
        return $this->expirationDate;
    }
}
