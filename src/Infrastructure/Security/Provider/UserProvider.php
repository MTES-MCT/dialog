<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Provider;

use App\Application\CommandBusInterface;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommand;
use App\Domain\User\ProConnectUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\AttributesBasedUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface, AttributesBasedUserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepositoryInterface,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function loadUserByIdentifier(string $identifier, array $attributes = []): UserInterface
    {
        $user = $this->userRepository->findOneByEmail($identifier);
        if (!empty($attributes)) {
            if (!$user) {
                $user = $this->commandBus->handle(new CreateProConnectUserCommand($identifier, $attributes));
            }
        }

        if (!$user instanceof User || (!$user->getPasswordUser() && empty($attributes))) {
            throw new UserNotFoundException(\sprintf('Unable to find the user %s', $identifier));
        }

        $userOrganizations = $this->organizationUserRepositoryInterface->findByUserUuid($user->getUuid());
        $isProConnectUser = $user->getProConnectUser() instanceof ProConnectUser;

        return new SymfonyUser(
            $user->getUuid(),
            $user->getEmail(),
            $user->getFullName(),
            $isProConnectUser ? SymfonyUser::DEFAULT_PRO_CONNECT_PASSWORD : $user->getPasswordUser()->getPassword(),
            $userOrganizations,
            $user->getRoles(),
            $user->isVerified(),
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
