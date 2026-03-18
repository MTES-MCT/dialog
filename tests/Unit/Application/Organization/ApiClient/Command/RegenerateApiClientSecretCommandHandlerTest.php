<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\ApiClient\Command;

use App\Application\Organization\ApiClient\Command\RegenerateApiClientSecretCommand;
use App\Application\Organization\ApiClient\Command\RegenerateApiClientSecretCommandHandler;
use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Exception\ApiClientNotFoundException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class RegenerateApiClientSecretCommandHandlerTest extends TestCase
{
    private MockObject $apiClientRepository;
    private MockObject $passwordHasherFactory;

    protected function setUp(): void
    {
        $this->apiClientRepository = $this->createMock(ApiClientRepositoryInterface::class);
        $this->passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
    }

    public function testRegeneratesSecretAndReturnsView(): void
    {
        $apiClientUuid = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $apiClient = new ApiClient($apiClientUuid);
        $apiClient->setClientId('client-123')->setClientSecret('old-hash')->setIsActive(true);

        $this->apiClientRepository->method('find')->with($apiClientUuid)->willReturn($apiClient);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn('new-hashed-secret');
        $this->passwordHasherFactory->method('getPasswordHasher')->willReturn($hasher);

        $handler = new RegenerateApiClientSecretCommandHandler(
            $this->apiClientRepository,
            $this->passwordHasherFactory,
        );

        $result = ($handler)(new RegenerateApiClientSecretCommand($apiClientUuid));

        self::assertSame('client-123', $result->clientId);
        self::assertNotEmpty($result->clientSecret);
        self::assertNotSame('old-hash', $apiClient->getClientSecret());
    }

    public function testThrowsWhenApiClientNotFound(): void
    {
        $this->apiClientRepository->method('find')->willReturn(null);

        $handler = new RegenerateApiClientSecretCommandHandler(
            $this->apiClientRepository,
            $this->passwordHasherFactory,
        );

        $this->expectException(ApiClientNotFoundException::class);
        ($handler)(new RegenerateApiClientSecretCommand('missing-uuid'));
    }
}
