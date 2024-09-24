<?php

declare(strict_types=1);

namespace App\Domain\VisaModel;

use App\Domain\User\Organization;

class VisaModel
{
    private string $name;
    private array $visas;
    private ?string $description;
    private ?Organization $organization;

    public function __construct(
        private string $uuid,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getVisas(): array
    {
        return $this->visas;
    }

    public function setVisas(array $visas): self
    {
        $this->visas = $visas;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function update(
        string $name,
        array $visas,
        ?string $description = null,
    ): void {
        $this->name = $name;
        $this->visas = $visas;
        $this->description = $description;
    }
}
