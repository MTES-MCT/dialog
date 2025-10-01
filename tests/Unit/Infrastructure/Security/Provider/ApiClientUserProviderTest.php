<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security\Provider;

use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Domain\User\Organization;
use App\Infrastructure\Security\Provider\ApiClientUserProvider;
use App\Infrastructure\Security\User\ApiClientUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApiClientUserProviderTest extends TestCase
{
    public function testLoadUserByIdentifierSuccess(): void
    {
        $repo = $this->createMock(ApiClientRepositoryInterface::class);
        $organization = (new Organization('11111111-1111-1111-1111-111111111111'))
            ->setName('Org')
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'));

        $apiClient = (new ApiClient('22222222-2222-2222-2222-222222222222'))
            ->setClientId('client-id')
            ->setClientSecret('client-secret')
            ->setOrganization($organization)
            ->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable('2024-01-02'));

        $repo->method('findOneByClientId')->with('client-id')->willReturn($apiClient);

        $provider = new ApiClientUserProvider($repo);
        $user = $provider->loadUserByIdentifier('client-id');

        $this->assertSame('client-id', $user->getUserIdentifier());
        $this->assertInstanceOf(PasswordAuthenticatedUserInterface::class, $user);
        $this->assertSame('client-secret', $user->getPassword());
    }

    public function testLoadUserByIdentifierNotFound(): void
    {
        $repo = $this->createMock(ApiClientRepositoryInterface::class);
        $repo->method('findOneByClientId')->with('missing')->willReturn(null);

        $provider = new ApiClientUserProvider($repo);

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('missing');
    }

    public function testLoadUserByIdentifierInactive(): void
    {
        $repo = $this->createMock(ApiClientRepositoryInterface::class);
        $organization = (new Organization('11111111-1111-1111-1111-111111111111'))
            ->setName('Org')
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'));

        $apiClient = (new ApiClient('22222222-2222-2222-2222-222222222222'))
            ->setClientId('client-id')
            ->setClientSecret('client-secret')
            ->setOrganization($organization)
            ->setIsActive(false)
            ->setCreatedAt(new \DateTimeImmutable('2024-01-02'));

        $repo->method('findOneByClientId')->with('client-id')->willReturn($apiClient);

        $provider = new ApiClientUserProvider($repo);

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('client-id');
    }

    public function testSupportsClass(): void
    {
        $repo = $this->createMock(ApiClientRepositoryInterface::class);
        $provider = new ApiClientUserProvider($repo);

        $this->assertTrue($provider->supportsClass(ApiClientUser::class));
        $this->assertFalse($provider->supportsClass(\stdClass::class));
    }

    public function testRefreshUser(): void
    {
        $repo = $this->createMock(ApiClientRepositoryInterface::class);
        $provider = new ApiClientUserProvider($repo);

        $organization = (new Organization('11111111-1111-1111-1111-111111111111'))
            ->setName('Org')
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'));

        $apiClient = (new ApiClient('22222222-2222-2222-2222-222222222222'))
            ->setClientId('client-id')
            ->setClientSecret('client-secret')
            ->setOrganization($organization)
            ->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable('2024-01-02'));

        $repo->method('findOneByClientId')->with('client-id')->willReturn($apiClient);

        $user = new ApiClientUser($apiClient);

        $refreshed = $provider->refreshUser($user);
        $this->assertInstanceOf(UserInterface::class, $refreshed);
        $this->assertSame('client-id', $refreshed->getUserIdentifier());
    }
}
