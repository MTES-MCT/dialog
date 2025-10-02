<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Provider;

use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Infrastructure\Security\User\ApiClientUser;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class ApiClientUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $apiClientRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $apiClient = $this->apiClientRepository->findOneByClientId($identifier);

        if (!$apiClient instanceof ApiClient) {
            throw new UserNotFoundException(\sprintf('Unable to find the api client %s', $identifier));
        }

        if (!$apiClient->isActive()) {
            throw new UserNotFoundException(\sprintf('The api client %s is not active', $identifier));
        }

        return new ApiClientUser($apiClient);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === ApiClientUser::class;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // No-op: credentials are managed via the entity and admin UI
    }
}
