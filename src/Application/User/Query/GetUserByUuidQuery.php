<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Application\QueryInterface;

class GetUserByUuidQuery implements QueryInterface
{
    public function __construct(
        public readonly string $uuid,
    ) {
    }
}
