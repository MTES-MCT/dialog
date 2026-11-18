<?php

declare(strict_types=1);

namespace App\Domain\Anct;

class TerritoryContact
{
    public function __construct(
        private string $siret,
        private string $email,
    ) {
    }

    public function getSiret(): string
    {
        return $this->siret;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
