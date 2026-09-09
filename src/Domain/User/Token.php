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
        private \DateTimeInterface $createdAt,
        private \DateTimeInterface $expirationDate,
        private ?\DateTimeInterface $usedAt = null,
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getExpirationDate(): \DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function markAsUsed(\DateTimeInterface $usedAt): void
    {
        $this->usedAt = $usedAt;
    }
}
