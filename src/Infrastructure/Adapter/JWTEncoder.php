<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use Firebase\JWT\JWT;

final class JWTEncoder
{
    public function encode(array $payload, string $secretKey): string
    {
        return JWT::encode($payload, $secretKey, 'HS256');
    }
}
