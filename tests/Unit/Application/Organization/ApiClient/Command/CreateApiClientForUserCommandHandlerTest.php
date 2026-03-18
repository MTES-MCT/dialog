<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\ApiClient\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\ApiClient\Command\CreateApiClientForUserCommand;
use App\Application\Organization\ApiClient\Command\CreateApiClientForUserCommandHandler;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Application\User\Query\GetOrganizationUserQuery;
use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Exception\UserAlreadyHasApiClientForOrganizationException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class CreateApiClientForUserCommandHandlerTest extends TestCase
{
    private MockObject $apiClientRepository;
    private MockObject $idFactory;
    private MockObject $passwordHasherFactory;
    private MockObject $dateUtils;
    private MockObject $queryBus;

    protected function setUp(): void
    {
        $this->apiClientRepository = $this->createMock(ApiClientRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
    }

    public function testCreatesApiClientAndReturnsView(): void
    {
        $orgUuid = '8f9164ed-dc0f-4c98-ac18-2f590a1cfd22';
        $userUuid = '0b507871-8b5e-4575-b297-a630310fc06e';
        $organization = $this->createMock(Organization::class);
        $user = $this->createMock(User::class);
        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUser->method('getUser')->willReturn($user);

        $this->queryBus->method('handle')
            ->willReturnCallback(function ($query) use ($organization, $organizationUser) {
                if ($query instanceof GetOrganizationByUuidQuery) {
                    return $organization;
                }
                if ($query instanceof GetOrganizationUserQuery) {
                    return $organizationUser;
                }
                throw new \InvalidArgumentException('Unexpected query');
            });

        $this->apiClientRepository->method('findOneByOrganizationAndUser')
            ->with($organization, $user)
            ->willReturn(null);

        $this->idFactory->method('make')
            ->willReturnOnConsecutiveCalls('new-uuid-123', 'new-client-id-456');
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn('hashed-secret');
        $this->passwordHasherFactory->method('getPasswordHasher')->willReturn($hasher);
        $now = new \DateTimeImmutable('2025-01-01 12:00:00');
        $this->dateUtils->method('getNow')->willReturn($now);

        $this->apiClientRepository->expects(self::once())
            ->method('add')
            ->with(self::callback(function (ApiClient $client) use ($organization, $user) {
                return $client->getOrganization() === $organization
                    && $client->getUser() === $user
                    && $client->getClientId() === 'new-client-id-456'
                    && $client->isActive();
            }));

        $handler = new CreateApiClientForUserCommandHandler(
            $this->apiClientRepository,
            $this->idFactory,
            $this->passwordHasherFactory,
            $this->dateUtils,
            $this->queryBus,
        );

        $result = ($handler)(new CreateApiClientForUserCommand($orgUuid, $userUuid));

        self::assertSame('new-client-id-456', $result->clientId);
        self::assertNotEmpty($result->clientSecret);
    }

    public function testThrowsWhenUserAlreadyHasApiClient(): void
    {
        $orgUuid = '8f9164ed-dc0f-4c98-ac18-2f590a1cfd22';
        $userUuid = '0b507871-8b5e-4575-b297-a630310fc06e';
        $organization = $this->createMock(Organization::class);
        $user = $this->createMock(User::class);
        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUser->method('getUser')->willReturn($user);
        $existing = $this->createMock(ApiClient::class);

        $this->queryBus->method('handle')
            ->willReturnCallback(function ($query) use ($organization, $organizationUser) {
                if ($query instanceof GetOrganizationByUuidQuery) {
                    return $organization;
                }
                if ($query instanceof GetOrganizationUserQuery) {
                    return $organizationUser;
                }
                throw new \InvalidArgumentException('Unexpected query');
            });

        $this->apiClientRepository->method('findOneByOrganizationAndUser')
            ->with($organization, $user)
            ->willReturn($existing);

        $this->apiClientRepository->expects(self::never())->method('add');

        $handler = new CreateApiClientForUserCommandHandler(
            $this->apiClientRepository,
            $this->idFactory,
            $this->passwordHasherFactory,
            $this->dateUtils,
            $this->queryBus,
        );

        $this->expectException(UserAlreadyHasApiClientForOrganizationException::class);
        ($handler)(new CreateApiClientForUserCommand($orgUuid, $userUuid));
    }
}
