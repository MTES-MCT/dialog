<?php

declare(strict_types=1);

namespace App\Domain\VisaModel;

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

    public function update(
        string $name,
        array $visas,
        ?string $description = null,
    ) {
        $this->name = $name;
        $this->visas = $visas;
        $this->description = $description;
    }
}
