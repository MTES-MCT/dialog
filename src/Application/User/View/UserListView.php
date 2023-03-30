<?php

declare(strict_types=1);

namespace App\Application\User\View;

class UserListView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $fullName,
        public readonly string $email,
        ) {
    }
}
