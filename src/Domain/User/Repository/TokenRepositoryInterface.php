<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Token;

interface TokenRepositoryInterface
{
    public function add(Token $token): Token;

    public function remove(Token $token): void;

    public function findOneByTokenAndType(string $token, string $type): ?Token;
}
