<?php

declare(strict_types=1);

namespace App\Application\User\View;

final readonly class InvitationView
{
    public function __construct(
        public string $uuid,
        public string $fullName,
        public string $email,
        public string $role,
    ) {
    }
}
