<?php

declare(strict_types=1);

namespace App\Domain\User;

class Feedback
{
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
}
