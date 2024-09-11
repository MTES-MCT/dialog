<?php

declare(strict_types=1);

namespace App\Domain\Visa;

use App\Domain\User\Organization;

class VisaModel
{
    public function __construct(
        private string $uuid,
        private string $name,
        private array $visas,
        private ?string $description,
        private ?Organization $organization,
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getVisas(): array
    {
        return $this->visas;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }
}
