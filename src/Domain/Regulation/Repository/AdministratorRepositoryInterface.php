<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

interface AdministratorRepositoryInterface
{
    public function findAll(): array;
}
