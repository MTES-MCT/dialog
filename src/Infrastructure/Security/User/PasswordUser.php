<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\User;

use App\Domain\User\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class PasswordUser extends AbstractAuthenticatedUser implements PasswordAuthenticatedUserInterface
{
    private string $password;
    private bool $isVerified;

    public function __construct(
        User $user,
        array $userOrganizations,
    ) {
        parent::__construct($user, $userOrganizations);

        $this->password = $user->getPasswordUser()->getPassword();
        $this->isVerified = $user->isVerified();
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getAuthOrigin(): string
    {
        return 'local';
    }
}
