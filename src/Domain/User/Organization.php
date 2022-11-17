<?php

declare(strict_types=1);

namespace App\Domain\User;

use Doctrine\Common\Collections\ArrayCollection;

class Organization
{
    private ArrayCollection $users;

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
}
