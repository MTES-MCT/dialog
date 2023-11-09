<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\User;
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
        $user = $this->getSymfonyUser();

        return $user
            ? $this->entityManager->getReference(User::class, $user->getUuid())
            : null;
    }

    public function getSymfonyUser(): ?SymfonyUser
    {
        /** @var SymfonyUser|null */
        $user = $this->security->getUser();

        return $user;
    }
}
