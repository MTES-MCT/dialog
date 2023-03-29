<?php

declare(strict_types=1);

namespace App\Domain\User;

class Organization
{
    /** @var User[] */
    private iterable $users = [];

    public function __construct(
        private string $uuid,
        private string $name,
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

    public function getUsers(): iterable
    {
        return $this->users;
    }

    public function addUser(User $user): void
    {
        $this->users[] = $user;
    }

    public function update(
        string $name,
    ): void {
        $this->name = $name;
    }
}
