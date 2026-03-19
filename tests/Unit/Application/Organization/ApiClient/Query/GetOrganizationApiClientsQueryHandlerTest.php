<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\ApiClient\Query;

use App\Application\Organization\ApiClient\Query\GetOrganizationApiClientsQuery;
use App\Application\Organization\ApiClient\Query\GetOrganizationApiClientsQueryHandler;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetOrganizationApiClientsQueryHandlerTest extends TestCase
{
    private MockObject $apiClientRepository;
    private MockObject $organizationUserRepository;
    private MockObject $queryBus;

    protected function setUp(): void
    {
        $this->apiClientRepository = $this->createMock(ApiClientRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
    }

    public function testReturnsApiClientViews(): void
    {
        $orgUuid = '8f9164ed-dc0f-4c98-ac18-2f590a1cfd22';
        $organization = $this->createMock(Organization::class);
        $organization->method('getUuid')->willReturn($orgUuid);

        $user = $this->createMock(User::class);
        $user->method('getUuid')->willReturn('0b507871-8b5e-4575-b297-a630310fc06e');
        $user->method('getFullName')->willReturn('Mathieu MARCHOIS');
        $user->method('getEmail')->willReturn('mathieu@beta.gouv.fr');

        $apiClient = new ApiClient('a1b2c3d4-e5f6-7890-abcd-ef1234567890');
        $apiClient->setClientId('client-123')
            ->setClientSecret('hashed')
            ->setOrganization($organization)
            ->setUser($user)
            ->setCreatedAt(new \DateTimeImmutable('2025-01-01'))
            ->setLastUsedAt(new \DateTimeImmutable('2025-01-15'))
            ->setIsActive(true);

        $orgUser = $this->createMock(\App\Domain\User\OrganizationUser::class);
        $orgUser->method('isOwner')->willReturn(false);

        $this->queryBus->method('handle')
            ->willReturnCallback(function ($query) use ($organization) {
                if ($query instanceof GetOrganizationByUuidQuery) {
                    return $organization;
                }

                throw new \InvalidArgumentException('Unexpected query');
            });

        $this->apiClientRepository->expects(self::once())
            ->method('findByOrganization')
            ->with($organization)
            ->willReturn([$apiClient]);

        $this->organizationUserRepository->expects(self::once())
            ->method('findOrganizationUser')
            ->with($orgUuid, '0b507871-8b5e-4575-b297-a630310fc06e')
            ->willReturn($orgUser);

        $handler = new GetOrganizationApiClientsQueryHandler(
            $this->apiClientRepository,
            $this->organizationUserRepository,
            $this->queryBus,
        );

        $result = ($handler)(new GetOrganizationApiClientsQuery($orgUuid));

        self::assertCount(1, $result);
        self::assertSame('a1b2c3d4-e5f6-7890-abcd-ef1234567890', $result[0]->uuid);
        self::assertSame('client-123', $result[0]->clientId);
        self::assertSame('Mathieu MARCHOIS', $result[0]->userFullName);
        self::assertSame('mathieu@beta.gouv.fr', $result[0]->userEmail);
        self::assertFalse($result[0]->isOwner);
    }

    public function testReturnsEmptyWhenNoApiClients(): void
    {
        $orgUuid = '8f9164ed-dc0f-4c98-ac18-2f590a1cfd22';
        $organization = $this->createMock(Organization::class);

        $this->queryBus->method('handle')
            ->willReturnCallback(function ($query) use ($organization) {
                if ($query instanceof GetOrganizationByUuidQuery) {
                    return $organization;
                }

                throw new \InvalidArgumentException('Unexpected query');
            });

        $this->apiClientRepository->expects(self::once())
            ->method('findByOrganization')
            ->with($organization)
            ->willReturn([]);

        $handler = new GetOrganizationApiClientsQueryHandler(
            $this->apiClientRepository,
            $this->organizationUserRepository,
            $this->queryBus,
        );

        $result = ($handler)(new GetOrganizationApiClientsQuery($orgUuid));

        self::assertCount(0, $result);
    }
}
