<?php

declare(strict_types=1);

namespace App\Application;

interface MattermostInterface
{
    public function post(string $text): void;
}
