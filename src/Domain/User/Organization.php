<?php

declare(strict_types=1);

namespace App\Domain\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Organization
{
    private Collection $users;
    private string $name;

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

    public function __toString(): string
    {
        return $this->name;
    }
}
