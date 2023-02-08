<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Provider;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneByEmail($identifier);

        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('Unable to find the user %s', $identifier));
        }

        return new SymfonyUser(
            $user->getUuid(),
            $user->getEmail(),
            $user->getFullName(),
            $user->getPassword(),
            ['ROLE_USER'],
            $user->getOrganization(),
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
