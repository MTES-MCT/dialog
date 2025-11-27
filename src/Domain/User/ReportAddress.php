<?php

declare(strict_types=1);

namespace App\Domain\User;

class ReportAddress
{
    private ?\DateTimeInterface $createdAt = null;

    public function __construct(
        private string $uuid,
        private string $content,
        private string $roadType,
        private User $user,
        private bool $hasBeenContacted = false,
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

    public function getRoadType(): string
    {
        return $this->roadType;
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

    public function getHasBeenContacted(): bool
    {
        return $this->hasBeenContacted;
    }

    public function setHasBeenContacted(bool $hasBeenContacted): self
    {
        $this->hasBeenContacted = $hasBeenContacted;

        return $this;
    }
}
