<?php

declare(strict_types=1);

namespace App\Application;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
}
