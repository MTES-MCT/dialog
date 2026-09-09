<?php

declare(strict_types=1);

namespace App\Application;

interface TchapInterface
{
    public function post(string $body, string $formattedBody): void;
}
