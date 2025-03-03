<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\User;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class AuthenticatedUser
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getUser(): ?User
    {
        $user = $this->getSessionUser();

        return $user
            ? $this->entityManager->getReference(User::class, $user->getUuid())
            : null;
    }

    public function getSessionUser(): ?AbstractAuthenticatedUser
    {
        $user = $this->security->getUser();

        return $user instanceof AbstractAuthenticatedUser ? $user : null;
    }
}
