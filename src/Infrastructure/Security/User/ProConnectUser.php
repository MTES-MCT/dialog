<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\User;

use App\Domain\User\User;

class ProConnectUser extends AbstractAuthenticatedUser
{
    private bool $isValidated;

    public function __construct(
        User $user,
        array $userOrganizations,
    ) {
        parent::__construct($user, $userOrganizations);

        $this->isValidated = $user->isValidated();
    }

    public function isVerified(): bool
    {
        return true;
    }

    public function isValidated(): bool
    {
        return $this->isValidated;
    }

    public function getAuthOrigin(): string
    {
        return 'proconnect';
    }
}
