<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Organization;

use App\Domain\Organization\ApiClient;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

class ApiClientTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = $this->createMock(Organization::class);

        $apiClient = new ApiClient(
            uuid: '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
        );
        $user = $this->createMock(\App\Domain\User\User::class);
        $apiClient->setClientId('clientId')
            ->setClientSecret('clientSecret')
            ->setOrganization($organization)
            ->setUser($user)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsActive(true)
            ->setLastUsedAt(new \DateTimeImmutable());

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $apiClient->getUuid());
        $this->assertSame('clientId', $apiClient->getClientId());
        $this->assertSame('clientSecret', $apiClient->getClientSecret());
        $this->assertSame($organization, $apiClient->getOrganization());
        $this->assertSame($user, $apiClient->getUser());
        $this->assertInstanceOf(\DateTimeInterface::class, $apiClient->getCreatedAt());
        $this->assertTrue($apiClient->isActive());
        $this->assertInstanceOf(\DateTimeInterface::class, $apiClient->getLastUsedAt());
    }

    public function testUserCanBeNull(): void
    {
        $apiClient = new ApiClient(uuid: '9cebe00d-04d8-48da-89b1-059f6b7bfe44');
        $this->assertNull($apiClient->getUser());
        $apiClient->setUser(null);
        $this->assertNull($apiClient->getUser());
    }
}
