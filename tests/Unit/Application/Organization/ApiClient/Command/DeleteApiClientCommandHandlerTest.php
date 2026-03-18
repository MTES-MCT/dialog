<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\ApiClient\Command;

use App\Application\Organization\ApiClient\Command\DeleteApiClientCommand;
use App\Application\Organization\ApiClient\Command\DeleteApiClientCommandHandler;
use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Exception\ApiClientNotFoundException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteApiClientCommandHandlerTest extends TestCase
{
    private MockObject $apiClientRepository;

    protected function setUp(): void
    {
        $this->apiClientRepository = $this->createMock(ApiClientRepositoryInterface::class);
    }

    public function testRemovesApiClient(): void
    {
        $apiClientUuid = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $apiClient = new ApiClient($apiClientUuid);

        $this->apiClientRepository->method('find')->with($apiClientUuid)->willReturn($apiClient);
        $this->apiClientRepository->expects(self::once())->method('remove')->with($apiClient);

        $handler = new DeleteApiClientCommandHandler(
            $this->apiClientRepository,
        );

        ($handler)(new DeleteApiClientCommand($apiClientUuid));
    }

    public function testThrowsWhenApiClientNotFound(): void
    {
        $this->apiClientRepository->method('find')->willReturn(null);
        $this->apiClientRepository->expects(self::never())->method('remove');

        $handler = new DeleteApiClientCommandHandler(
            $this->apiClientRepository,
        );

        $this->expectException(ApiClientNotFoundException::class);
        ($handler)(new DeleteApiClientCommand('missing-uuid'));
    }
}
