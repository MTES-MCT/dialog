<?php

declare(strict_types=1);

namespace App\Application\User\View;

final class UserOrganizationView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $name,
        public readonly array $roles = [],
    ) {
    }
}
