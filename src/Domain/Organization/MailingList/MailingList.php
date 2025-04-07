<?php

declare(strict_types=1);

namespace App\Domain\Organization\MailingList;

use App\Domain\User\Organization;

class MailingList
{
    public function __construct(
        private string $uuid,
        private string $name,
        private string $email,
        private Organization $organization,
        private ?string $function = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFunction(): ?string
    {
        return $this->function;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }
}
