<?php

declare(strict_types=1);

namespace App\Domain\User;

final readonly class UserExportView
{
    public function __construct(
        public string $fullName,
        public string $email,
    ) {
    }
}
