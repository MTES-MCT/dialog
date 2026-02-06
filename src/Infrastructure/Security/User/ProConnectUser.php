<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\User;

class ProConnectUser extends AbstractAuthenticatedUser
{
    public function getAuthOrigin(): string
    {
        return 'proconnect';
    }
}
