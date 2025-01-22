<?php

declare(strict_types=1);

namespace App\Domain\User;

final class TokenGenerator
{
    public function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
