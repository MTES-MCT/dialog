<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\ApiClient\Command;

use App\Application\ApiClientSecretHasherInterface;
use App\Application\Organization\ApiClient\Command\RegenerateApiClientSecretCommand;
use App\Application\Organization\ApiClient\Command\RegenerateApiClientSecretCommandHandler;
use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Exception\ApiClientNotFoundException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RegenerateApiClientSecretCommandHandlerTest extends TestCase
{
    private MockObject $apiClientRepository;
    private MockObject $apiClientSecretHasher;

    protected function setUp(): void
    {
        $this->apiClientRepository = $this->createMock(ApiClientRepositoryInterface::class);
        $this->apiClientSecretHasher = $this->createMock(ApiClientSecretHasherInterface::class);
    }

    public function testRegeneratesSecretAndReturnsView(): void
    {
        $apiClientUuid = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $apiClient = new ApiClient($apiClientUuid);
        $apiClient->setClientId('client-123')->setClientSecret('old-hash')->setIsActive(true);

        $this->apiClientRepository->method('findOneByUuid')->with($apiClientUuid)->willReturn($apiClient);
        $this->apiClientSecretHasher->method('hash')->willReturn('new-hashed-secret');

        $handler = new RegenerateApiClientSecretCommandHandler(
            $this->apiClientRepository,
            $this->apiClientSecretHasher,
        );

        $result = ($handler)(new RegenerateApiClientSecretCommand($apiClientUuid));

        self::assertSame('client-123', $result->clientId);
        self::assertNotEmpty($result->clientSecret);
        self::assertNotSame('old-hash', $apiClient->getClientSecret());
    }

    public function testThrowsWhenApiClientNotFound(): void
    {
        $this->apiClientRepository->method('findOneByUuid')->willReturn(null);

        $handler = new RegenerateApiClientSecretCommandHandler(
            $this->apiClientRepository,
            $this->apiClientSecretHasher,
        );

        $this->expectException(ApiClientNotFoundException::class);
        ($handler)(new RegenerateApiClientSecretCommand('missing-uuid'));
    }
}
