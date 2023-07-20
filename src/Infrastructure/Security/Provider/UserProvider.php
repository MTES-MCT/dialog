<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Provider;

use App\Domain\User\Organization;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Security\SymfonyUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneByEmail($identifier);

        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('Unable to find the user %s', $identifier));
        }

        $organizations = [];
        $role = 'ROLE_USER';

        foreach ($user->getOrganizations() as $organization) {
            $organizations[] = $this->entityManager->getReference(Organization::class, $organization->getUuid());

            // Users of the DiaLog organization are considered administrators
            if ($organization->getUuid() === 'e0d93630-acf7-4722-81e8-ff7d5fa64b66') {
                $role = 'ROLE_ADMIN';
            }
        }

        return new SymfonyUser(
            $user->getUuid(),
            $user->getEmail(),
            $user->getFullName(),
            $user->getPassword(),
            $organizations,
            [$role],
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SymfonyUser::class === $class;
    }
}
