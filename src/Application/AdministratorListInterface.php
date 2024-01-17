<?php

declare(strict_types=1);

namespace App\Application;

interface AdministratorListInterface
{
    public function findAll(): array;
}
