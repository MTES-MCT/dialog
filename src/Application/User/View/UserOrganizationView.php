<?php

declare(strict_types=1);

namespace App\Application\User\View;

final readonly class UserOrganizationView
{
    public function __construct(
        public string $uuid,
        public string $name,
        public bool $completed = false,
        public array $roles = [],
    ) {
    }
}
