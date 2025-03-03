<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\User;

class ProConnectUser extends AbstractAuthenticatedUser
{
    public function isVerified(): bool
    {
        return true;
    }

    public function getAuthOrigin(): string
    {
        return 'proconnect';
    }
}
