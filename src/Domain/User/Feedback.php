<?php

declare(strict_types=1);

namespace App\Domain\User;

class Feedback
{
    private ?\DateTimeInterface $createdAt = null;

    public function __construct(
        private string $uuid,
        private string $content,
        private bool $consentToBeContacted,
        private User $user,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function isConsentToBeContacted(): bool
    {
        return $this->consentToBeContacted;
    }

    public function getUser(): User
    {
        return $this->user;
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
}
