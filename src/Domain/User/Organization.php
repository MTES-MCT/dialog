<?php

declare(strict_types=1);

namespace App\Domain\User;

class Organization
{
    /** @var User[] */
    private array $users = [];

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

    public function getUsers(): array
    {
        return $this->users;
    }

    public function addUser(User $user): void
    {
        if (\in_array($user, $this->users, true)) {
            return;
        }

        array_push($this->users, $user);
    }
}
