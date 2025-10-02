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
        $apiClient->setClientId('clientId')
            ->setClientSecret('clientSecret')
            ->setOrganization($organization)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsActive(true)
            ->setLastUsedAt(new \DateTimeImmutable());

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $apiClient->getUuid());
        $this->assertSame('clientId', $apiClient->getClientId());
        $this->assertSame('clientSecret', $apiClient->getClientSecret());
        $this->assertSame($organization, $apiClient->getOrganization());
        $this->assertInstanceOf(\DateTimeInterface::class, $apiClient->getCreatedAt());
        $this->assertTrue($apiClient->isActive());
        $this->assertInstanceOf(\DateTimeInterface::class, $apiClient->getLastUsedAt());
    }
}
