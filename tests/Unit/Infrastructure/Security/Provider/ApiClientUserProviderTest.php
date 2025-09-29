<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security\Provider;

use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Domain\User\Organization;
use App\Infrastructure\Security\Provider\ApiClientUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

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
}
