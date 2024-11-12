<?php

declare(strict_types=1);

namespace App\Domain\User;

class Organization
{
    private string $name;
    private ?string $siret;
    private ?string $logo;

    public function __construct(
        private string $uuid,
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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function update(string $name, string $siret): void
    {
        $this->name = $name;
        $this->siret = $siret;
    }
}
