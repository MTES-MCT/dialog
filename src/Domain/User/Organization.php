<?php

declare(strict_types=1);

namespace App\Domain\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Organization
{
    private Collection $users;
    private string $name;
    private ?string $siret;

    public function __construct(
        private string $uuid,
    ) {
        $this->users = new ArrayCollection();
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

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): void
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }
    }

    public function removeUser(User $user): void
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
