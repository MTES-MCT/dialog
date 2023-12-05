<?php

declare(strict_types=1);

namespace App\Domain\France;

class City
{
    public function __construct(
        private string $inseeCode,
        private string $name,
        private string $departement,
    ) {
    }

    public function getInseeCode(): string
    {
        return $this->inseeCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDepartement(): string
    {
        return $this->departement;
    }
}
