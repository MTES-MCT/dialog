<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\Command;

use App\Application\DateUtilsInterface;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommand;
use App\Application\Organization\Command\UpdateApiClientLastUsedAtCommandHandler;
use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Exception\ApiClientNotFoundException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateApiClientLastUsedAtCommandHandlerTest extends TestCase
{
    private MockObject $apiClientRepository;
    private MockObject $dateUtils;

    protected function setUp(): void
    {
        $this->apiClientRepository = $this->createMock(ApiClientRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testUpdateLastUsedAt(): void
    {
        $clientId = 'client-123';
        $command = new UpdateApiClientLastUsedAtCommand(clientId: $clientId);

        $apiClient = new ApiClient(uuid: '11111111-2222-3333-4444-555555555555');
        $apiClient->setClientId($clientId);

        $now = new \DateTimeImmutable('2025-01-01T12:00:00+00:00');

        $this->apiClientRepository
            ->expects(self::once())
            ->method('findOneByClientId')
            ->with($clientId)
            ->willReturn($apiClient);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $handler = new UpdateApiClientLastUsedAtCommandHandler(
            apiClientRepository: $this->apiClientRepository,
            dateUtils: $this->dateUtils,
        );

        $result = $handler($command);

        self::assertSame($apiClient, $result);
        self::assertSame($now, $apiClient->getLastUsedAt());
    }

    public function testThrowsWhenApiClientNotFound(): void
    {
        $clientId = 'missing-client';
        $command = new UpdateApiClientLastUsedAtCommand(clientId: $clientId);

        $this->apiClientRepository
            ->expects(self::once())
            ->method('findOneByClientId')
            ->with($clientId)
            ->willReturn(null);

        $handler = new UpdateApiClientLastUsedAtCommandHandler(
            apiClientRepository: $this->apiClientRepository,
            dateUtils: $this->dateUtils,
        );

        $this->expectException(ApiClientNotFoundException::class);

        $handler($command);
    }
}
